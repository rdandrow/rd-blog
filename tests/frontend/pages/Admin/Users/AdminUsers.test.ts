import AdminUsers from '@/pages/Admin/Users/AdminUsers.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div data-testid="head">{{ title }}</div>', props: ['title'] },
    router: {
        post: vi.fn(),
        patch: vi.fn(),
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

describe('AdminUsers Page', () => {
    const mockAdmins = {
        data: [
            {
                id: 1,
                name: 'John Doe',
                email: 'john@example.com',
                role: 'admin',
                created_at: '2024-01-01',
                invitation_sent_at: '2024-01-01',
                invitation_accepted_at: '2024-01-02',
                invitation_expired: false,
            },
            {
                id: 2,
                name: 'Jane Smith',
                email: 'jane@example.com',
                role: 'master_admin',
                created_at: '2024-01-05',
                invitation_sent_at: '2024-01-05',
                invitation_accepted_at: null,
                invitation_expired: true,
            },
        ],
        links: {},
        meta: {},
    };

    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toBe('Admin Users');
        });

        it('should render within AppLayout', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const layout = wrapper.findComponent({ name: 'AppLayout' });
            expect(layout.exists()).toBe(true);
        });

        it('should render page heading', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            expect(wrapper.text()).toContain('Admin Users');
        });

        it('should render page description', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            expect(wrapper.text()).toContain('Manage admin and master admin users');
        });

        it('should render invite button', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const inviteButton = buttons.find(b => b.text().includes('Invite Admin User'));
            expect(inviteButton).toBeTruthy();
        });
    });

    describe('table', () => {
        it('should render table headers', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            expect(wrapper.text()).toContain('Name');
            expect(wrapper.text()).toContain('Email');
            expect(wrapper.text()).toContain('Role');
            expect(wrapper.text()).toContain('Invitation Status');
            expect(wrapper.text()).toContain('Created');
            expect(wrapper.text()).toContain('Actions');
        });

        it('should render admin users', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            expect(wrapper.text()).toContain('John Doe');
            expect(wrapper.text()).toContain('john@example.com');
            expect(wrapper.text()).toContain('Jane Smith');
            expect(wrapper.text()).toContain('jane@example.com');
        });

        it('should render role badges', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            expect(wrapper.text()).toContain('Admin');
            expect(wrapper.text()).toContain('Master Admin');
        });

        it('should render invitation statuses', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            expect(wrapper.text()).toContain('Accepted');
            expect(wrapper.text()).toContain('Expired');
        });

        it('should render action buttons', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            expect(wrapper.text()).toContain('Edit Role');
            expect(wrapper.text()).toContain('Delete');
        });

        it('should show resend invitation button for expired invitations', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            expect(wrapper.text()).toContain('Resend Invitation');
        });
    });

    describe('role formatting', () => {
        it('should format admin role', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            expect(vm.formatRole('admin')).toBe('Admin');
        });

        it('should format master_admin role', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            expect(vm.formatRole('master_admin')).toBe('Master Admin');
        });
    });

    describe('invitation status', () => {
        it('should return accepted status', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            const status = vm.getInvitationStatus(mockAdmins.data[0]);
            expect(status.text).toBe('Accepted');
        });

        it('should return expired status', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            const status = vm.getInvitationStatus(mockAdmins.data[1]);
            expect(status.text).toBe('Expired');
        });

        it('should return pending status', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            const pendingUser = {
                invitation_sent_at: '2024-01-01',
                invitation_accepted_at: null,
                invitation_expired: false,
            };
            const status = vm.getInvitationStatus(pendingUser);
            expect(status.text).toBe('Pending');
        });

        it('should return N/A for no invitation', () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            const noInviteUser = {
                invitation_sent_at: null,
                invitation_accepted_at: null,
                invitation_expired: false,
            };
            const status = vm.getInvitationStatus(noInviteUser);
            expect(status.text).toBe('N/A');
        });
    });

    describe('create dialog', () => {
        it('should open create dialog when button clicked', async () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const inviteButton = buttons.find(b => b.text().includes('Invite Admin User'));
            await inviteButton?.trigger('click');

            const vm = wrapper.vm as any;
            expect(vm.showCreateDialog).toBe(true);
        });

        it('should render create dialog content', async () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.showCreateDialog = true;
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Invite Admin User');
            expect(wrapper.text()).toContain('Send an invitation email');
        });

        it('should have name input in create dialog', async () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.showCreateDialog = true;
            await wrapper.vm.$nextTick();

            const inputs = wrapper.findAllComponents({ name: 'Input' });
            const nameInput = inputs.find(i => i.props('id') === 'name');
            expect(nameInput?.props('placeholder')).toBe('Full name');
        });

        it('should have email input in create dialog', async () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.showCreateDialog = true;
            await wrapper.vm.$nextTick();

            const inputs = wrapper.findAllComponents({ name: 'Input' });
            const emailInput = inputs.find(i => i.props('id') === 'email');
            expect(emailInput?.props('type')).toBe('email');
        });

        it('should have role select in create dialog', async () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.showCreateDialog = true;
            await wrapper.vm.$nextTick();

            const select = wrapper.find('select#role');
            expect(select.exists()).toBe(true);
        });
    });

    describe('edit dialog', () => {
        it('should open edit dialog', async () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.openEditDialog(mockAdmins.data[0]);

            expect(vm.showEditDialog).toBe(true);
            expect(vm.selectedUser).toEqual(mockAdmins.data[0]);
        });

        it('should render edit dialog content', async () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.openEditDialog(mockAdmins.data[0]);
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Edit User Role');
            expect(wrapper.text()).toContain('John Doe');
        });
    });

    describe('delete dialog', () => {
        it('should open delete dialog', async () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.openDeleteDialog(mockAdmins.data[0]);

            expect(vm.showDeleteDialog).toBe(true);
            expect(vm.selectedUser).toEqual(mockAdmins.data[0]);
        });

        it('should render delete dialog content', async () => {
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.openDeleteDialog(mockAdmins.data[0]);
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Delete User');
            expect(wrapper.text()).toContain('Are you sure you want to delete');
        });
    });

    describe('user actions', () => {
        it('should call createUser', async () => {
            vi.mocked(router.post).mockClear();
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.createForm = { name: 'Test', email: 'test@example.com', role: 'admin' };
            vm.createUser();

            expect(router.post).toHaveBeenCalledWith(
                '/admin/users',
                { name: 'Test', email: 'test@example.com', role: 'admin' },
                expect.any(Object)
            );
        });

        it('should call updateUserRole', async () => {
            vi.mocked(router.patch).mockClear();
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.selectedUser = mockAdmins.data[0];
            vm.editForm.role = 'master_admin';
            vm.updateUserRole();

            expect(router.patch).toHaveBeenCalledWith(
                '/admin/users/1/role',
                { role: 'master_admin' },
                expect.any(Object)
            );
        });

        it('should call deleteUser', async () => {
            vi.mocked(router.delete).mockClear();
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
            });

            const vm = wrapper.vm as any;
            vm.selectedUser = mockAdmins.data[0];
            vm.deleteUser();

            expect(router.delete).toHaveBeenCalledWith(
                '/admin/users/1',
                expect.any(Object)
            );
        });

        it('should call resendInvitation', async () => {
            vi.mocked(router.post).mockClear();
            const wrapper = mount(AdminUsers, {
                props: { admins: mockAdmins },
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
