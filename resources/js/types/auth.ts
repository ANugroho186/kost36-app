export type UserRole = 'owner' | 'tenant';

export type User = {
    id: number;
    name: string;
    username: string;
    // Opsional: akun tanpa email tetap bisa login dengan username.
    email: string | null;
    role: UserRole;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
