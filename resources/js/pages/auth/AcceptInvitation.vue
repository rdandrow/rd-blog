<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    token: string;
    email: string;
    name: string;
}

const props = defineProps<Props>();

const form = ref({
    password: '',
    password_confirmation: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const isPasswordValid = computed(() => {
    return form.value.password.length >= 14;
});

const isPasswordConfirmed = computed(() => {
    return form.value.password === form.value.password_confirmation && 
           form.value.password_confirmation.length > 0;
});

const isFormValid = computed(() => {
    return isPasswordValid.value && isPasswordConfirmed.value;
});

function submit() {
    processing.value = true;
    errors.value = {};
    
    router.post(`/invitation/accept/${props.token}`, form.value, {
        onError: (responseErrors) => {
            errors.value = responseErrors;
            processing.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <Head title="Accept Invitation" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                    Welcome to {{ $page.props.appName || 'the platform' }}
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Hi {{ name }}, set up your password to get started
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 sm:p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <Label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Email
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            :modelValue="email"
                            disabled
                            class="mt-1 bg-gray-50 dark:bg-gray-900"
                        />
                    </div>

                    <div>
                        <Label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Password
                        </Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder="Enter your password (minimum 14 characters)"
                            class="mt-1"
                            :class="{ 'border-red-500': errors.password }"
                        />
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ errors.password }}
                        </p>
                        <p v-else-if="form.password.length > 0 && !isPasswordValid" class="mt-1 text-sm text-amber-600 dark:text-amber-400">
                            Password must be at least 14 characters
                        </p>
                        <p v-else-if="isPasswordValid" class="mt-1 text-sm text-green-600 dark:text-green-400">
                            ✓ Password meets requirements
                        </p>
                    </div>

                    <div>
                        <Label for="password_confirmation" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Confirm Password
                        </Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder="Confirm your password"
                            class="mt-1"
                            :class="{ 'border-red-500': errors.password_confirmation }"
                        />
                        <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ errors.password_confirmation }}
                        </p>
                        <p v-else-if="form.password_confirmation.length > 0 && !isPasswordConfirmed" class="mt-1 text-sm text-amber-600 dark:text-amber-400">
                            Passwords do not match
                        </p>
                        <p v-else-if="isPasswordConfirmed" class="mt-1 text-sm text-green-600 dark:text-green-400">
                            ✓ Passwords match
                        </p>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                    Password Requirements
                                </h3>
                                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li>At least 14 characters long</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Button
                        type="submit"
                        :disabled="processing || !isFormValid"
                        class="w-full"
                    >
                        <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ processing ? 'Setting Password...' : 'Set Password & Continue' }}
                    </Button>
                </form>
            </div>

            <p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
                This invitation link will expire in 48 hours
            </p>
        </div>
    </div>
</template>
