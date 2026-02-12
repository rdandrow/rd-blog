import { describe, expect, it, beforeEach, vi } from 'vitest';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';

// Mock the routes module
vi.mock('@/routes/two-factor', () => ({
    qrCode: {
        url: vi.fn(() => '/user/two-factor-qr-code'),
    },
    recoveryCodes: {
        url: vi.fn(() => '/user/two-factor-recovery-codes'),
    },
    secretKey: {
        url: vi.fn(() => '/user/two-factor-secret-key'),
    },
}));

// Mock fetch globally
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('useTwoFactorAuth', () => {
    beforeEach(() => {
        mockFetch.mockClear();
        
        // Clear the module-level state between tests
        const { clearTwoFactorAuthData } = useTwoFactorAuth();
        clearTwoFactorAuthData();
    });

    describe('initial state', () => {
        it('should initialize with default state', () => {
            const { qrCodeSvg, manualSetupKey, recoveryCodesList, errors } = useTwoFactorAuth();
            
            expect(qrCodeSvg.value).toBe(null);
            expect(manualSetupKey.value).toBe(null);
            expect(recoveryCodesList.value).toEqual([]);
            expect(errors.value).toEqual([]);
        });

        it('should have hasSetupData computed as false initially', () => {
            const { hasSetupData } = useTwoFactorAuth();
            
            expect(hasSetupData.value).toBe(false);
        });
    });

    describe('fetchQrCode', () => {
        it('should fetch and set QR code SVG', async () => {
            const mockResponse = {
                svg: '<svg>mock qr code</svg>',
                url: 'otpauth://...',
            };
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => mockResponse,
            });

            const { fetchQrCode, qrCodeSvg, errors } = useTwoFactorAuth();
            
            await fetchQrCode();
            
            expect(qrCodeSvg.value).toBe('<svg>mock qr code</svg>');
            expect(errors.value).toEqual([]);
        });

        it('should handle fetch errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            const { fetchQrCode, qrCodeSvg, errors } = useTwoFactorAuth();
            
            await fetchQrCode();
            
            expect(qrCodeSvg.value).toBe(null);
            expect(errors.value).toContain('Failed to fetch QR code');
        });
    });

    describe('fetchSetupKey', () => {
        it('should fetch and set setup key', async () => {
            const mockResponse = {
                secretKey: 'ABCD1234EFGH5678',
            };
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => mockResponse,
            });

            const { fetchSetupKey, manualSetupKey, errors } = useTwoFactorAuth();
            
            await fetchSetupKey();
            
            expect(manualSetupKey.value).toBe('ABCD1234EFGH5678');
            expect(errors.value).toEqual([]);
        });

        it('should handle fetch errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            const { fetchSetupKey, manualSetupKey, errors } = useTwoFactorAuth();
            
            await fetchSetupKey();
            
            expect(manualSetupKey.value).toBe(null);
            expect(errors.value).toContain('Failed to fetch a setup key');
        });
    });

    describe('fetchSetupData', () => {
        it('should fetch both QR code and setup key', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ svg: '<svg>qr</svg>', url: 'otpauth://...' }),
            });
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ secretKey: 'KEY123' }),
            });

            const { fetchSetupData, qrCodeSvg, manualSetupKey, errors } = useTwoFactorAuth();
            
            await fetchSetupData();
            
            expect(qrCodeSvg.value).toBe('<svg>qr</svg>');
            expect(manualSetupKey.value).toBe('KEY123');
            expect(errors.value).toEqual([]);
        });

        it('should clear errors before fetching', async () => {
            const { fetchSetupData, errors } = useTwoFactorAuth();
            
            // Add some errors first
            errors.value.push('Previous error');
            
            mockFetch.mockResolvedValue({
                ok: true,
                json: async () => ({ svg: '<svg>qr</svg>', url: 'otpauth://...', secretKey: 'KEY' }),
            });
            
            await fetchSetupData();
            
            expect(errors.value).toEqual([]);
        });

        it('should handle errors by setting values to null', async () => {
            // Both fetch calls need to fail for fetchSetupData to set values to null
            mockFetch.mockRejectedValueOnce(new Error('Network error')); // First call (QR code)
            mockFetch.mockRejectedValueOnce(new Error('Network error')); // Second call (Setup key)

            const { fetchSetupData, qrCodeSvg, manualSetupKey } = useTwoFactorAuth();
            
            await fetchSetupData();
            
            // When Promise.all rejects, the catch block sets both to null
            expect(qrCodeSvg.value).toBe(null);
            expect(manualSetupKey.value).toBe(null);
        });
    });

    describe('fetchRecoveryCodes', () => {
        it('should fetch and set recovery codes', async () => {
            const mockCodes = ['CODE1-1234', 'CODE2-5678', 'CODE3-9012'];
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => mockCodes,
            });

            const { fetchRecoveryCodes, recoveryCodesList, errors } = useTwoFactorAuth();
            
            await fetchRecoveryCodes();
            
            expect(recoveryCodesList.value).toEqual(mockCodes);
            expect(errors.value).toEqual([]);
        });

        it('should clear errors before fetching', async () => {
            const { fetchRecoveryCodes, errors } = useTwoFactorAuth();
            
            errors.value.push('Previous error');
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ['CODE1', 'CODE2'],
            });
            
            await fetchRecoveryCodes();
            
            expect(errors.value).toEqual([]);
        });

        it('should handle fetch errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            const { fetchRecoveryCodes, errors, recoveryCodesList } = useTwoFactorAuth();
            
            await fetchRecoveryCodes();
            
            expect(errors.value).toContain('Failed to fetch recovery codes');
            expect(recoveryCodesList.value).toEqual([]);
        });

        it('should handle empty recovery codes array', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => [],
            });

            const { fetchRecoveryCodes, recoveryCodesList } = useTwoFactorAuth();
            
            await fetchRecoveryCodes();
            
            expect(recoveryCodesList.value).toEqual([]);
        });
    });

    describe('clearSetupData', () => {
        it('should clear QR code and setup key', async () => {
            mockFetch.mockResolvedValue({
                ok: true,
                json: async () => ({ svg: '<svg>qr</svg>', url: 'otpauth://...', secretKey: 'KEY' }),
            });

            const { fetchSetupData, clearSetupData, qrCodeSvg, manualSetupKey } = useTwoFactorAuth();
            
            await fetchSetupData();
            expect(qrCodeSvg.value).toBe('<svg>qr</svg>');
            expect(manualSetupKey.value).toBe('KEY');
            
            clearSetupData();
            
            expect(qrCodeSvg.value).toBe(null);
            expect(manualSetupKey.value).toBe(null);
        });

        it('should clear errors', () => {
            const { clearSetupData, errors } = useTwoFactorAuth();
            
            errors.value.push('Some error');
            
            clearSetupData();
            
            expect(errors.value).toEqual([]);
        });

        it('should not affect recovery codes', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ['CODE1', 'CODE2'],
            });

            const { fetchRecoveryCodes, clearSetupData, recoveryCodesList } = useTwoFactorAuth();
            
            await fetchRecoveryCodes();
            expect(recoveryCodesList.value).toEqual(['CODE1', 'CODE2']);
            
            clearSetupData();
            
            expect(recoveryCodesList.value).toEqual(['CODE1', 'CODE2']);
        });
    });

    describe('clearErrors', () => {
        it('should clear all errors', () => {
            const { clearErrors, errors } = useTwoFactorAuth();
            
            errors.value.push('Error 1');
            errors.value.push('Error 2');
            
            clearErrors();
            
            expect(errors.value).toEqual([]);
        });
    });

    describe('clearTwoFactorAuthData', () => {
        it('should clear all data including recovery codes', async () => {
            // Set up data
            mockFetch.mockResolvedValue({
                ok: true,
                json: async () => ({ svg: '<svg>qr</svg>', url: 'otpauth://...', secretKey: 'KEY' }),
            });
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ['CODE1', 'CODE2'],
            });

            const { fetchSetupData, fetchRecoveryCodes, clearTwoFactorAuthData, qrCodeSvg, manualSetupKey, recoveryCodesList, errors } = useTwoFactorAuth();
            
            await fetchSetupData();
            await fetchRecoveryCodes();
            
            errors.value.push('Some error');
            
            clearTwoFactorAuthData();
            
            expect(qrCodeSvg.value).toBe(null);
            expect(manualSetupKey.value).toBe(null);
            expect(recoveryCodesList.value).toEqual([]);
            expect(errors.value).toEqual([]);
        });
    });

    describe('hasSetupData computed', () => {
        it('should return false when no data', () => {
            const { hasSetupData } = useTwoFactorAuth();
            
            expect(hasSetupData.value).toBe(false);
        });

        it('should return false when only qrCode exists', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ svg: '<svg>qr</svg>', url: 'otpauth://...' }),
            });

            const { fetchQrCode, hasSetupData } = useTwoFactorAuth();
            
            await fetchQrCode();
            
            expect(hasSetupData.value).toBe(false);
        });

        it('should return false when only setupKey exists', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ secretKey: 'KEY123' }),
            });

            const { fetchSetupKey, hasSetupData } = useTwoFactorAuth();
            
            await fetchSetupKey();
            
            expect(hasSetupData.value).toBe(false);
        });

        it('should return true when both exist', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ svg: '<svg>qr</svg>', url: 'otpauth://...' }),
            });
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ secretKey: 'KEY123' }),
            });

            const { fetchSetupData, hasSetupData } = useTwoFactorAuth();
            
            await fetchSetupData();
            
            expect(hasSetupData.value).toBe(true);
        });

        it('should be reactive', async () => {
            mockFetch.mockResolvedValue({
                ok: true,
                json: async () => ({ svg: '<svg>qr</svg>', url: 'otpauth://...', secretKey: 'KEY' }),
            });

            const { fetchSetupData, clearSetupData, hasSetupData } = useTwoFactorAuth();
            
            expect(hasSetupData.value).toBe(false);
            
            await fetchSetupData();
            expect(hasSetupData.value).toBe(true);
            
            clearSetupData();
            expect(hasSetupData.value).toBe(false);
        });
    });

    describe('reactivity', () => {
        it('should have reactive qrCodeSvg', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ svg: '<svg>qr1</svg>', url: 'otpauth://...' }),
            });

            const { fetchQrCode, qrCodeSvg } = useTwoFactorAuth();
            
            const values: (string | null)[] = [];
            values.push(qrCodeSvg.value);
            
            await fetchQrCode();
            values.push(qrCodeSvg.value);
            
            expect(values).toEqual([null, '<svg>qr1</svg>']);
        });

        it('should have reactive recoveryCodesList', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ['CODE1', 'CODE2'],
            });

            const { fetchRecoveryCodes, recoveryCodesList } = useTwoFactorAuth();
            
            const lengths: number[] = [];
            lengths.push(recoveryCodesList.value.length);
            
            await fetchRecoveryCodes();
            lengths.push(recoveryCodesList.value.length);
            
            expect(lengths).toEqual([0, 2]);
        });

        it('should have reactive errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            const { fetchQrCode, errors } = useTwoFactorAuth();
            
            const lengths: number[] = [];
            lengths.push(errors.value.length);
            
            await fetchQrCode();
            lengths.push(errors.value.length);
            
            expect(lengths).toEqual([0, 1]);
        });
    });

    describe('HTTP error responses', () => {
        it('should handle non-ok response in fetchQrCode', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 500,
                json: async () => ({}),
            });

            const { fetchQrCode, qrCodeSvg, errors } = useTwoFactorAuth();
            
            await fetchQrCode();
            
            expect(qrCodeSvg.value).toBeNull();
            expect(errors.value).toContain('Failed to fetch QR code');
        });

        it('should handle 404 response in fetchSetupKey', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 404,
                json: async () => ({}),
            });

            const { fetchSetupKey, manualSetupKey, errors } = useTwoFactorAuth();
            
            await fetchSetupKey();
            
            expect(manualSetupKey.value).toBeNull();
            expect(errors.value).toContain('Failed to fetch a setup key');
        });

        it('should handle 401 unauthorized response', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 401,
                json: async () => ({}),
            });

            const { fetchRecoveryCodes, recoveryCodesList, errors } = useTwoFactorAuth();
            
            await fetchRecoveryCodes();
            
            expect(recoveryCodesList.value).toEqual([]);
            expect(errors.value).toContain('Failed to fetch recovery codes');
        });

        it('should handle Promise.all rejection in fetchSetupData', async () => {
            // First call for QR code succeeds
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ svg: '<svg></svg>', url: 'otpauth://...' }),
            });
            
            // Second call for setup key fails
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 500,
                json: async () => ({}),
            });

            const { fetchSetupData, qrCodeSvg, manualSetupKey, errors } = useTwoFactorAuth();
            
            await fetchSetupData();
            
            // When one fetch fails, it sets its own value to null and adds error
            // The successful fetch value remains
            expect(manualSetupKey.value).toBeNull();
            expect(errors.value).toContain('Failed to fetch a setup key');
        });

        it('should handle network errors in fetch', async () => {
            mockFetch.mockRejectedValueOnce(new TypeError('Failed to fetch'));

            const { fetchQrCode, qrCodeSvg, errors } = useTwoFactorAuth();
            
            await fetchQrCode();
            
            expect(qrCodeSvg.value).toBeNull();
            expect(errors.value).toContain('Failed to fetch QR code');
        });
    });
});
