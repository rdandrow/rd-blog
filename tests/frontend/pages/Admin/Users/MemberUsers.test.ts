import MemberUsers from '@/pages/Admin/Users/MemberUsers.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div data-testid="head">{{ title }}</div>', props: ['title'] },
    router: {
        post: vi.fn(),
        delete: vi.fn(),
    },
}));

// Mock AppLayout
vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div data-testid="app-layout"><slot /></div>',
    },
}));

// Mock UI components
vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button :variant="variant" :size="size" @click="$emit(\'click\')"><slot /></button>',
        props: ['variant', 'size'],
        emits: ['click'],
    },
}));

vi.mock('@/components/ui/dialog', () => ({
    Dialog: {
        name: 'Dialog',
        template: '<div v-if="open"><slot /></div>',
        props: ['open'],
        emits: ['update:open'],
    },
    DialogContent: { name: 'DialogContent', template: '<div><slot /></div>' },
    DialogDescription: { name: 'DialogDescription', template: '<p><slot /></p>' },
    DialogFooter: { name: 'DialogFooter', template: '<div><slot /></div>' },
    DialogHeader: { name: 'DialogHeader', template: '<div><slot /></div>' },
    DialogTitle: { name: 'DialogTitle', template: '<h2><slot /></h2>' },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input :id="id" :type="type" :placeholder="placeholder" :modelValue="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
        props: ['id', 'type', 'placeholder', 'modelValue'],
        emits: ['update:modelValue'],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: {
        name: 'Label',
        template: '<label :for="forAttr"><slot /></label>',
        props: { for: String },
        computed: {
            forAttr(): string {
                return (this as any).for;
            },
        },
    },
}));

const createWrapper = (props: any) => mount(MemberUsers, props as any);

describe('MemberUsers Page', () => {
    const mockMembers = {
        data: [
            {
                id: 1,
                name: 'Alice Johnson',
                email: 'alice@example.com',
                role: 'member',
                created_at: '2024-01-10',
                invitation_sent_at: '2024-01-10',
                invitation_accepted_at: '2024-01-11',
                invitation_expired: false,
            },
            {
                id: 2,
                name: 'Bob Williams',
                email: 'bob@example.com',
                role: 'member',
                created_at: '2024-01-15',
                invitation_sent_at: '2024-01-15',
                invitation_accepted_at: null,
                invitation_expired: true,
            },
        ],
        links: {},
        meta: {},
    };

    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toBe('Member Users');
        });

        it('should render within AppLayout', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const layout = wrapper.findComponent({ name: 'AppLayout' });
            expect(layout.exists()).toBe(true);
        });

        it('should render page heading', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            expect(wrapper.text()).toContain('Member Users');
        });

        it('should render page description', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            expect(wrapper.text()).toContain('Manage member users');
        });

        it('should render invite button', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const inviteButton = buttons.find(b => b.text().includes('Invite Member User'));
            expect(inviteButton).toBeTruthy();
        });
    });

    describe('table', () => {
        it('should render table headers', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            expect(wrapper.text()).toContain('Name');
            expect(wrapper.text()).toContain('Email');
            expect(wrapper.text()).toContain('Invitation Status');
            expect(wrapper.text()).toContain('Created');
            expect(wrapper.text()).toContain('Actions');
        });

        it('should render member users', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            expect(wrapper.text()).toContain('Alice Johnson');
            expect(wrapper.text()).toContain('alice@example.com');
            expect(wrapper.text()).toContain('Bob Williams');
            expect(wrapper.text()).toContain('bob@example.com');
        });

        it('should render invitation statuses', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            expect(wrapper.text()).toContain('Accepted');
            expect(wrapper.text()).toContain('Expired');
        });

        it('should render delete buttons', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const deleteButtons = buttons.filter(b => b.text().includes('Delete'));
            expect(deleteButtons.length).toBeGreaterThan(0);
        });

        it('should show resend invitation button for expired invitations', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            expect(wrapper.text()).toContain('Resend Invitation');
        });
    });

    describe('invitation status', () => {
        it('should return accepted status', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            const status = vm.getInvitationStatus(mockMembers.data[0]);
            expect(status.text).toBe('Accepted');
            expect(status.class).toContain('bg-green-100');
        });

        it('should return expired status', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            const status = vm.getInvitationStatus(mockMembers.data[1]);
            expect(status.text).toBe('Expired');
            expect(status.class).toContain('bg-red-100');
        });

        it('should return pending status', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            const pendingUser = {
                invitation_sent_at: '2024-01-01',
                invitation_accepted_at: null,
                invitation_expired: false,
            };
            const status = vm.getInvitationStatus(pendingUser);
            expect(status.text).toBe('Pending');
            expect(status.class).toContain('bg-yellow-100');
        });

        it('should return N/A for no invitation', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            const noInviteUser = {
                invitation_sent_at: null,
                invitation_accepted_at: null,
                invitation_expired: false,
            };
            const status = vm.getInvitationStatus(noInviteUser);
            expect(status.text).toBe('N/A');
            expect(status.class).toContain('bg-gray-100');
        });
    });

    describe('create dialog', () => {
        it('should open create dialog when button clicked', async () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const inviteButton = buttons.find(b => b.text().includes('Invite Member User'));
            await inviteButton?.trigger('click');

            const vm = wrapper.vm as any;
            expect(vm.showCreateDialog).toBe(true);
        });

        it('should render create dialog content', async () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.showCreateDialog = true;
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Invite Member User');
            expect(wrapper.text()).toContain('Send an invitation email');
        });

        it('should have name input in create dialog', async () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.showCreateDialog = true;
            await wrapper.vm.$nextTick();

            const inputs = wrapper.findAllComponents({ name: 'Input' });
            const nameInput = inputs.find(i => i.props('id') === 'name');
            expect(nameInput?.props('placeholder')).toBe('Full name');
        });

        it('should have email input in create dialog', async () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.showCreateDialog = true;
            await wrapper.vm.$nextTick();

            const inputs = wrapper.findAllComponents({ name: 'Input' });
            const emailInput = inputs.find(i => i.props('id') === 'email');
            expect(emailInput?.props('type')).toBe('email');
        });

        it('should reset form when opening create dialog', () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.createForm = { name: 'Old', email: 'old@example.com', role: 'admin' };
            vm.openCreateDialog();

            expect(vm.createForm.name).toBe('');
            expect(vm.createForm.email).toBe('');
            expect(vm.createForm.role).toBe('member');
        });
    });

    describe('delete dialog', () => {
        it('should open delete dialog', async () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.openDeleteDialog(mockMembers.data[0]);

            expect(vm.showDeleteDialog).toBe(true);
            expect(vm.selectedUser).toEqual(mockMembers.data[0]);
        });

        it('should render delete dialog content', async () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.openDeleteDialog(mockMembers.data[0]);
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Delete User');
            expect(wrapper.text()).toContain('Are you sure you want to delete');
        });

        it('should display user name in delete dialog', async () => {
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.openDeleteDialog(mockMembers.data[0]);
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Alice Johnson');
        });
    });

    describe('user actions', () => {
        it('should call createUser', async () => {
            vi.mocked(router.post).mockClear();
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.createForm = { name: 'Test', email: 'test@example.com', role: 'member' };
            vm.createUser();

            expect(router.post).toHaveBeenCalledWith(
                '/admin/users',
                { name: 'Test', email: 'test@example.com', role: 'member' },
                expect.any(Object)
            );
        });

        it('should close dialog on successful create', () => {
            vi.mocked(router.post).mockImplementation((url, data, options) => {
                if (options?.onSuccess) {
                    options.onSuccess({} as any);
                }
                return Promise.resolve();
            });

            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.showCreateDialog = true;
            vm.createUser();

            expect(vm.showCreateDialog).toBe(false);
        });

        it('should call deleteUser', async () => {
            vi.mocked(router.delete).mockClear();
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.selectedUser = mockMembers.data[0];
            vm.deleteUser();

            expect(router.delete).toHaveBeenCalledWith(
                '/admin/users/1',
                expect.any(Object)
            );
        });

        it('should not delete if no user selected', () => {
            vi.mocked(router.delete).mockClear();
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.selectedUser = null;
            vm.deleteUser();

            expect(router.delete).not.toHaveBeenCalled();
        });

        it('should call resendInvitation', async () => {
            vi.mocked(router.post).mockClear();
            const wrapper = createWrapper({
                props: { members: mockMembers },
            });

            const vm = wrapper.vm as any;
            vm.resendInvitation(2);

            expect(router.post).toHaveBeenCalledWith(
                '/admin/users/2/resend-invitation',
                {},
                expect.any(Object)
            );
        });
    });
});
