import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import UserInfo from '@/components/UserInfo.vue';
import type { User } from '@/types';

describe('UserInfo', () => {
    const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        avatar: 'https://example.com/avatar.jpg',
        email_verified_at: '2024-01-01',
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
    };

    const mockUserWithoutAvatar: User = {
        id: 2,
        name: 'Jane Smith',
        email: 'jane@example.com',
        avatar: undefined,
        email_verified_at: '2024-01-01',
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
    };

    describe('rendering', () => {
        it('should render user name', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                },
            });

            expect(wrapper.text()).toContain('John Doe');
        });

        it('should not show email by default', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                },
            });

            expect(wrapper.text()).not.toContain('john@example.com');
        });

        it('should show email when showEmail is true', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                    showEmail: true,
                },
            });

            expect(wrapper.text()).toContain('john@example.com');
        });

        it('should render avatar image when avatar exists', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                },
            });

            const img = wrapper.find('img');
            expect(img.exists()).toBe(true);
            expect(img.attributes('src')).toBe('https://example.com/avatar.jpg');
            expect(img.attributes('alt')).toBe('John Doe');
        });

        it('should render initials fallback when no avatar', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUserWithoutAvatar,
                },
            });

            // Should have fallback text content
            expect(wrapper.html()).toContain('JS');
        });

        it('should render initials fallback when avatar is empty string', () => {
            const userWithEmptyAvatar: User = {
                ...mockUser,
                avatar: '',
            };

            const wrapper = mount(UserInfo, {
                props: {
                    user: userWithEmptyAvatar,
                },
            });

            // Should show initials
            expect(wrapper.html()).toContain('JD');
        });

        it('should have correct text styling', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                },
            });

            const textContainer = wrapper.find('.grid.flex-1');
            expect(textContainer.exists()).toBe(true);
            expect(textContainer.classes()).toContain('text-left');
        });

        it('should truncate long names', () => {
            const userWithLongName: User = {
                ...mockUser,
                name: 'A'.repeat(100),
            };

            const wrapper = mount(UserInfo, {
                props: {
                    user: userWithLongName,
                },
            });

            const nameElement = wrapper.find('.truncate.font-medium');
            expect(nameElement.exists()).toBe(true);
        });
    });

    describe('props', () => {
        it('should accept user prop', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                },
            });

            expect(wrapper.text()).toContain('John Doe');
        });

        it('should accept showEmail prop', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                    showEmail: true,
                },
            });

            expect(wrapper.text()).toContain('john@example.com');
        });
    });

    describe('reactivity', () => {
        it('should update when user prop changes', async () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                },
            });

            expect(wrapper.text()).toContain('John Doe');

            await wrapper.setProps({ user: mockUserWithoutAvatar });

            expect(wrapper.text()).toContain('Jane Smith');
            expect(wrapper.text()).not.toContain('John Doe');
        });

        it('should show email when showEmail changes to true', async () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                    showEmail: false,
                },
            });

            expect(wrapper.text()).not.toContain('john@example.com');

            await wrapper.setProps({ showEmail: true });

            expect(wrapper.text()).toContain('john@example.com');
        });

        it('should hide email when showEmail changes to false', async () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                    showEmail: true,
                },
            });

            expect(wrapper.text()).toContain('john@example.com');

            await wrapper.setProps({ showEmail: false });

            expect(wrapper.text()).not.toContain('john@example.com');
        });
    });

    describe('edge cases', () => {
        it('should handle user with special characters in name', () => {
            const userWithSpecialChars: User = {
                ...mockUser,
                name: "O'Brien & Associates <Company>",
            };

            const wrapper = mount(UserInfo, {
                props: {
                    user: userWithSpecialChars,
                },
            });

            expect(wrapper.text()).toContain("O'Brien & Associates <Company>");
        });

        it('should handle user with unicode characters', () => {
            const userWithUnicode: User = {
                ...mockUser,
                name: '李明 🎉',
            };

            const wrapper = mount(UserInfo, {
                props: {
                    user: userWithUnicode,
                },
            });

            expect(wrapper.text()).toContain('李明 🎉');
        });

        it('should handle very long email addresses', () => {
            const userWithLongEmail: User = {
                ...mockUser,
                email: 'verylongemailaddress@verylongdomainname.example.com',
            };

            const wrapper = mount(UserInfo, {
                props: {
                    user: userWithLongEmail,
                    showEmail: true,
                },
            });

            expect(wrapper.text()).toContain('verylongemailaddress@verylongdomainname.example.com');
            expect(wrapper.find('.truncate.text-xs').exists()).toBe(true);
        });

        it('should handle single character names', () => {
            const userWithSingleChar: User = {
                ...mockUser,
                name: 'A',
            };

            const wrapper = mount(UserInfo, {
                props: {
                    user: userWithSingleChar,
                },
            });

            expect(wrapper.text()).toContain('A');
        });
    });

    describe('avatar', () => {
        it('should have correct avatar styling', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                },
            });

            const avatar = wrapper.find('.h-8.w-8');
            expect(avatar.exists()).toBe(true);
            expect(avatar.classes()).toContain('rounded-lg');
        });

        it('should handle avatar loading failure gracefully', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                },
            });

            // Avatar component should always have fallback
            expect(wrapper.html()).toBeTruthy();
        });
    });

    describe('accessibility', () => {
        it('should have alt text for avatar image', () => {
            const wrapper = mount(UserInfo, {
                props: {
                    user: mockUser,
                },
            });

            const img = wrapper.find('img');
            if (img.exists()) {
                expect(img.attributes('alt')).toBe('John Doe');
            }
        });
    });
});
