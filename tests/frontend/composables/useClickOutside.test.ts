import { describe, expect, it, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, ref, h } from 'vue';
import { useClickOutside } from '@/composables/useClickOutside';

describe('useClickOutside', () => {
    let targetElement: HTMLElement;
    let outsideElement: HTMLElement;

    beforeEach(() => {
        // Create DOM elements for testing
        document.body.innerHTML = '';
        
        targetElement = document.createElement('div');
        targetElement.id = 'target';
        
        outsideElement = document.createElement('div');
        outsideElement.id = 'outside';
        
        document.body.appendChild(targetElement);
        document.body.appendChild(outsideElement);
    });

    it('should call callback when clicking outside element', async () => {
        const callback = vi.fn();
        const targetRef = ref<HTMLElement | null>(targetElement);

        const TestComponent = defineComponent({
            setup() {
                useClickOutside(targetRef, callback);
                return () => h('div');
            },
        });

        const wrapper = mount(TestComponent);

        // Click outside
        outsideElement.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(callback).toHaveBeenCalledTimes(1);

        wrapper.unmount();
    });

    it('should not call callback when clicking inside element', async () => {
        const callback = vi.fn();
        const targetRef = ref<HTMLElement | null>(targetElement);

        const TestComponent = defineComponent({
            setup() {
                useClickOutside(targetRef, callback);
                return () => h('div');
            },
        });

        const wrapper = mount(TestComponent);

        // Click inside
        targetElement.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(callback).not.toHaveBeenCalled();

        wrapper.unmount();
    });

    it('should not call callback when clicking on the element itself', async () => {
        const callback = vi.fn();
        const targetRef = ref<HTMLElement | null>(targetElement);

        const TestComponent = defineComponent({
            setup() {
                useClickOutside(targetRef, callback);
                return () => h('div');
            },
        });

        const wrapper = mount(TestComponent);

        // Click on the element itself
        targetElement.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(callback).not.toHaveBeenCalled();

        wrapper.unmount();
    });

    it('should handle null element ref gracefully', async () => {
        const callback = vi.fn();
        const targetRef = ref<HTMLElement | null>(null);

        const TestComponent = defineComponent({
            setup() {
                useClickOutside(targetRef, callback);
                return () => h('div');
            },
        });

        const wrapper = mount(TestComponent);

        // Click anywhere
        outsideElement.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        // When element is null, callback should NOT be called (element.contains() won't run)
        expect(callback).not.toHaveBeenCalled();

        wrapper.unmount();
    });

    it('should work with nested elements', async () => {
        const callback = vi.fn();
        
        const nestedChild = document.createElement('span');
        nestedChild.id = 'nested';
        targetElement.appendChild(nestedChild);
        
        const targetRef = ref<HTMLElement | null>(targetElement);

        const TestComponent = defineComponent({
            setup() {
                useClickOutside(targetRef, callback);
                return () => h('div');
            },
        });

        const wrapper = mount(TestComponent);

        // Click on nested child (should not trigger callback)
        nestedChild.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(callback).not.toHaveBeenCalled();

        // Click outside
        outsideElement.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(callback).toHaveBeenCalledTimes(1);

        wrapper.unmount();
    });

    it('should clean up event listener on unmount', async () => {
        const callback = vi.fn();
        const targetRef = ref<HTMLElement | null>(targetElement);
        const removeEventListenerSpy = vi.spyOn(document, 'removeEventListener');

        const TestComponent = defineComponent({
            setup() {
                useClickOutside(targetRef, callback);
                return () => h('div');
            },
        });

        const wrapper = mount(TestComponent);
        wrapper.unmount();

        expect(removeEventListenerSpy).toHaveBeenCalledWith(
            'click',
            expect.any(Function),
            true
        );

        removeEventListenerSpy.mockRestore();
    });

    it('should use capture phase for event listening', async () => {
        const callback = vi.fn();
        const targetRef = ref<HTMLElement | null>(targetElement);
        const addEventListenerSpy = vi.spyOn(document, 'addEventListener');

        const TestComponent = defineComponent({
            setup() {
                useClickOutside(targetRef, callback);
                return () => h('div');
            },
        });

        const wrapper = mount(TestComponent);

        // Verify capture phase is used (third parameter is true)
        expect(addEventListenerSpy).toHaveBeenCalledWith(
            'click',
            expect.any(Function),
            true
        );

        addEventListenerSpy.mockRestore();
        wrapper.unmount();
    });

    it('should handle rapid clicks', async () => {
        const callback = vi.fn();
        const targetRef = ref<HTMLElement | null>(targetElement);

        const TestComponent = defineComponent({
            setup() {
                useClickOutside(targetRef, callback);
                return () => h('div');
            },
        });

        const wrapper = mount(TestComponent);

        // Rapid clicks outside
        for (let i = 0; i < 10; i++) {
            outsideElement.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        }

        expect(callback).toHaveBeenCalledTimes(10);

        wrapper.unmount();
    });

    it('should handle clicks on document body', async () => {
        const callback = vi.fn();
        const targetRef = ref<HTMLElement | null>(targetElement);

        const TestComponent = defineComponent({
            setup() {
                useClickOutside(targetRef, callback);
                return () => h('div');
            },
        });

        const wrapper = mount(TestComponent);

        // Click on body (outside)
        document.body.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(callback).toHaveBeenCalled();

        wrapper.unmount();
    });
});
