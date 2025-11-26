<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { router } from '@inertiajs/vue3';
import { Form, Head } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineProps<{
    status?: string;
}>();

const form = reactive({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    router.post('/admin/login', form, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            form.password = '';
        },
    });
};
</script>

<template>
    <AuthBase
        title="Admin Login"
        description="Enter your admin credentials to access the dashboard"
    >
        <Head title="Admin Login" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="admin@example.com"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox 
                            id="remember" 
                            v-model:checked="form.remember" 
                            :tabindex="3" 
                        />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                >
                    Log in as Admin
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Need an admin account?
                <TextLink href="/admin/register" :tabindex="5">Register here</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
