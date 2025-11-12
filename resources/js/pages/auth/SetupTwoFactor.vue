<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    qrCode: string;
    recoveryCodes: string[];
    secretKey: string;
}

const props = defineProps<Props>();

const step = ref<'setup' | 'verify' | 'recovery'>('setup');

const confirmForm = useForm({
    code: '',
});

const confirmTwoFactor = () => {
    confirmForm.post('/two-factor-challenge/confirm', {
        onSuccess: () => {
            // Success handled by redirect
        },
        onError: () => {
            confirmForm.reset('code');
        }
    });
};

const showRecoveryCodes = () => {
    step.value = 'recovery';
};

const proceedAfterRecovery = () => {
    router.get('/dashboard');
};

const qrCodeSvg = computed(() => {
    // Parse the SVG from the QR code string if needed
    return props.qrCode;
});
</script>

<template>
    <AuthLayout
        title="Setup Two-Factor Authentication"
        description="Two-factor authentication is required to secure your account"
    >
        <Head title="Setup 2FA" />

        <!-- Step 1: Setup Instructions -->
        <div v-if="step === 'setup'" class="space-y-6">
            <div class="text-center">
                <h3 class="text-lg font-semibold text-foreground mb-2">
                    Scan QR Code
                </h3>
                <p class="text-sm text-muted-foreground mb-4">
                    Use your authenticator app (Google Authenticator, Authy, etc.) to scan this QR code:
                </p>
            </div>

            <!-- QR Code -->
            <div class="flex justify-center">
                <div class="p-4 bg-white rounded-lg border">
                    <div v-html="qrCodeSvg"></div>
                </div>
            </div>

            <!-- Manual Entry -->
            <div class="text-center">
                <p class="text-xs text-muted-foreground mb-2">
                    Can't scan? Enter this key manually:
                </p>
                <code class="px-2 py-1 bg-muted text-muted-foreground rounded text-xs font-mono">
                    {{ secretKey }}
                </code>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <Button 
                    @click="step = 'verify'" 
                    class="w-full"
                    variant="default"
                >
                    I've Added the Account
                </Button>
            </div>
        </div>

        <!-- Step 2: Verify Setup -->
        <div v-else-if="step === 'verify'" class="space-y-6">
            <div class="text-center">
                <h3 class="text-lg font-semibold text-foreground mb-2">
                    Verify Setup
                </h3>
                <p class="text-sm text-muted-foreground mb-4">
                    Enter the 6-digit code from your authenticator app:
                </p>
            </div>

            <form @submit.prevent="confirmTwoFactor" class="space-y-4">
                <div class="grid gap-2">
                    <Label for="code">Authentication Code</Label>
                    <Input
                        id="code"
                        v-model="confirmForm.code"
                        type="text"
                        required
                        autofocus
                        placeholder="000000"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        class="text-center text-lg tracking-wider"
                        autocomplete="one-time-code"
                    />
                    <InputError :message="confirmForm.errors.code" />
                </div>

                <div class="space-y-3">
                    <Button 
                        type="submit" 
                        class="w-full"
                        :disabled="confirmForm.processing || confirmForm.code.length !== 6"
                    >
                        <Spinner v-if="confirmForm.processing" />
                        Verify & Enable 2FA
                    </Button>

                    <Button 
                        @click="step = 'setup'" 
                        variant="ghost" 
                        class="w-full"
                        type="button"
                    >
                        ← Back to QR Code
                    </Button>
                </div>
            </form>
        </div>

        <!-- Step 3: Recovery Codes -->
        <div v-else-if="step === 'recovery'" class="space-y-6">
            <div class="text-center">
                <h3 class="text-lg font-semibold text-foreground mb-2">
                    Save Recovery Codes
                </h3>
                <p class="text-sm text-muted-foreground mb-4">
                    Store these recovery codes in a safe place. You can use them to access your account if you lose your authentication device.
                </p>
            </div>

            <!-- Recovery Codes -->
            <div class="bg-muted p-4 rounded-lg">
                <div class="grid grid-cols-2 gap-2 text-sm font-mono">
                    <code 
                        v-for="code in recoveryCodes" 
                        :key="code"
                        class="px-2 py-1 bg-background rounded text-center"
                    >
                        {{ code }}
                    </code>
                </div>
            </div>

            <!-- Warning -->
            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-xs text-yellow-800">
                    ⚠️ <strong>Important:</strong> These codes will only be shown once. Save them securely before proceeding.
                </p>
            </div>

            <Button 
                @click="proceedAfterRecovery" 
                class="w-full"
            >
                I've Saved My Recovery Codes
            </Button>
        </div>

        <!-- Quick setup info -->
        <div class="mt-6 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-xs text-amber-800">
                � <strong>Required:</strong> Two-factor authentication is mandatory for all accounts to ensure security. Complete this setup to access your dashboard.
            </p>
        </div>
    </AuthLayout>
</template>

<style scoped>
/* Ensure QR code displays properly */
:deep(svg) {
    max-width: 200px;
    height: auto;
}
</style>