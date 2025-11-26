<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { reactive } from 'vue';

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'admin',
});

const errors = reactive<Record<string, string>>({});
const processing = reactive({ value: false });

const submit = () => {
    processing.value = true;
    router.post('/admin/register', form, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            form.password = '';
            form.password_confirmation = '';
        },
        onError: (err) => {
            Object.assign(errors, err);
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <AuthBase
        title="Create Admin Account"
        description="Register as an administrator to manage the blog"
    >
        <Head title="Admin Registration" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        v-model="form.name"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        placeholder="Full name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        placeholder="admin@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <!-- Admin Registration Notice -->
                <div class="grid gap-3 p-4 border rounded-lg bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800">
                    <div class="flex items-center space-x-2">
                        <div class="h-4 w-4 rounded-full bg-amber-500 flex items-center justify-center">
                            <svg class="h-2 w-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 16 16">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3v8m0 0l3-3m-3 3L5 8"/>
                            </svg>
                        </div>
                        <Label class="text-sm font-medium text-amber-800 dark:text-amber-200">
                            Admin Account Registration
                        </Label>
                    </div>
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        You'll have full access to manage blog posts, drafts, and administrative features.
                        Two-factor authentication will be required for security.
                    </p>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :tabindex="5"
                    :disabled="processing.value"
                >
                    <Spinner v-if="processing.value" />
                    Create Admin Account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an admin account?
                <TextLink href="/admin/login" :tabindex="6">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
