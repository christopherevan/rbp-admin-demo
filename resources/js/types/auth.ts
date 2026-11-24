export type Role = 'admin' | 'sales';

export type User = {
    id: number;
    name: string;
    username: string;
    role: Role;
};

export type Auth = {
    user: User | null;
    can: {
        archiveRecords: boolean;
        decideDescriptions: boolean;
        manageCatalog: boolean;
    } | null;
};
