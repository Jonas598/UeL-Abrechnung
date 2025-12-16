import apiClient from './apiClient';

export interface DepartmentDto {
    id: number;
    name: string;
}

export interface UserDto {
    id: number;
    name: string;
    vorname: string;
    email: string;
    isAdmin: boolean;
    isGeschaeftsstelle: boolean;
    departmentHeadDepartments: DepartmentDto[];
    trainerDepartments: DepartmentDto[];
}

export interface UpdateUserRolesPayload {
    isAdmin: boolean;
    isGeschaeftsstelle: boolean;
    roles: {
        departmentHead: number[];
        trainer: number[];
    };
}

export async function fetchUsers(): Promise<UserDto[]> {
    const response = await apiClient.get<{ users: UserDto[] }>('/admin/users');
    return response.data.users;
}

export async function updateUserRoles(userId: number, payload: UpdateUserRolesPayload): Promise<void> {
    await apiClient.put(`/admin/users/${userId}/roles`, payload);
}
