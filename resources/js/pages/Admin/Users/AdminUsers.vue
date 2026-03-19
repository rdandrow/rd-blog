<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Admin {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
    invitation_sent_at: string | null;
    invitation_accepted_at: string | null;
    invitation_expired: boolean;
}

interface PaginatedAdmins {
    data: Admin[];
    links: Record<string, any>;
    meta: Record<string, any>;
}

defineProps<{
    admins: PaginatedAdmins;
}>();

const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedUser = ref<Admin | null>(null);

const createForm = ref({
    name: '',
    email: '',
    role: 'admin',
});

const editForm = ref({
    role: '',
});

function openCreateDialog() {
    createForm.value = {
        name: '',
        email: '',
        role: 'admin',
    };
    showCreateDialog.value = true;
}

function openEditDialog(user: Admin) {
    selectedUser.value = user;
    editForm.value.role = user.role;
    showEditDialog.value = true;
}

function openDeleteDialog(user: Admin) {
    selectedUser.value = user;
    showDeleteDialog.value = true;
}

function createUser() {
    router.post('/admin/users', createForm.value, {
        onSuccess: () => {
            showCreateDialog.value = false;
        },
    });
}

function updateUserRole() {
    if (!selectedUser.value) return;

    router.patch(`/admin/users/${selectedUser.value.id}/role`, editForm.value, {
        onSuccess: () => {
            showEditDialog.value = false;
        },
    });
}

function deleteUser() {
    if (!selectedUser.value) return;

    router.delete(`/admin/users/${selectedUser.value.id}`, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}

function getRoleBadgeClass(role: string) {
    if (role === 'master_admin') {
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
    }
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
}

function formatRole(role: string) {
    return role === 'master_admin' ? 'Master Admin' : 'Admin';
}

function getInvitationStatus(user: Admin) {
    if (user.invitation_accepted_at) {
        return {
            text: 'Accepted',
            class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        };
    }
    if (user.invitation_expired) {
        return {
            text: 'Expired',
            class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        };
    }
    if (user.invitation_sent_at) {
        return {
            text: 'Pending',
            class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        };
    }
    return {
        text: 'N/A',
        class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
}

function resendInvitation(userId: number) {
    router.post(
        `/admin/users/${userId}/resend-invitation`,
        {},
        {
            onSuccess: () => {
                // Success message will be shown via flash message
            },
        },
    );
}
</script>

<template>
    <AppLayout>
        <Head title="Admin Users" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold">Admin Users</h2>
                        <p class="text-sm text-muted-foreground">
                            Manage admin and master admin users
                        </p>
                    </div>
                    <Button @click="openCreateDialog">
                        Invite Admin User
                    </Button>
                </div>

                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                        >
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Name
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Email
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Role
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Invitation Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Created
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800"
                            >
                                <tr
                                    v-for="admin in admins.data"
                                    :key="admin.id"
                                >
                                    <td
                                        class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100"
                                    >
                                        {{ admin.name }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                    >
                                        {{ admin.email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="
                                                getRoleBadgeClass(admin.role)
                                            "
                                        >
                                            {{ formatRole(admin.role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="
                                                getInvitationStatus(admin).class
                                            "
                                        >
                                            {{
                                                getInvitationStatus(admin).text
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                    >
                                        {{ admin.created_at }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap"
                                    >
                                        <Button
                                            v-if="
                                                admin.invitation_expired &&
                                                !admin.invitation_accepted_at
                                            "
                                            variant="outline"
                                            size="sm"
                                            @click="resendInvitation(admin.id)"
                                            class="mr-2"
                                        >
                                            Resend Invitation
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="openEditDialog(admin)"
                                            class="mr-2"
                                        >
                                            Edit Role
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="openDeleteDialog(admin)"
                                        >
                                            Delete
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Dialog -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Invite Admin User</DialogTitle>
                    <DialogDescription>
                        Send an invitation email to a new admin or master admin
                        user. They will receive a link to set their password.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="createForm.name"
                            placeholder="Full name"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="createForm.email"
                            type="email"
                            placeholder="email@example.com"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="role">Role</Label>
                        <select
                            id="role"
                            v-model="createForm.role"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="admin">Admin</option>
                            <option value="master_admin">Master Admin</option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCreateDialog = false">
                        Cancel
                    </Button>
                    <Button @click="createUser">Send Invitation</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Role Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit User Role</DialogTitle>
                    <DialogDescription>
                        Change the role for {{ selectedUser?.name }}
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="edit-role">Role</Label>
                        <select
                            id="edit-role"
                            v-model="editForm.role"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="admin">Admin</option>
                            <option value="master_admin">Master Admin</option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showEditDialog = false">
                        Cancel
                    </Button>
                    <Button @click="updateUserRole">Update Role</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete User Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete User</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete
                        {{ selectedUser?.name }}? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="deleteUser">
                        Delete User
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
