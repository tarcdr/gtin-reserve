import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SuccessButton from '@/Components/SuccessButton';
import TextInput from '@/Components/TextInput';
import Checkbox from '@/Components/Checkbox';
import { Head, router, useForm } from '@inertiajs/react';

const formatRoleLabel = (role) => (role ? role.toUpperCase() : '-');

export default function UserManagement({ auth, users = [], roles = [] }) {
    const [editingUser, setEditingUser] = useState(null);
    const [passwordUser, setPasswordUser] = useState(null);
    const [deletingUser, setDeletingUser] = useState(null);
    const currentLogin = auth?.user?.user_login;

    const createForm = useForm({
        employee_name: '',
        user_login: '',
        role: roles[0] || 'rd',
        is_active: true,
        password: '',
        password_confirmation: '',
    });

    const editForm = useForm({
        employee_name: '',
        role: roles[0] || 'rd',
        is_active: true,
    });

    const passwordForm = useForm({
        password: '',
        password_confirmation: '',
    });

    const deleteForm = useForm({});

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => createForm.reset('password', 'password_confirmation'),
        });
    };

    const openEdit = (user) => {
        setEditingUser(user);
        editForm.setData({
            employee_name: user.employee_name || '',
            role: user.role || roles[0] || 'rd',
            is_active: Boolean(user.is_active),
        });
    };

    const closeEdit = () => {
        setEditingUser(null);
        editForm.reset();
    };

    const submitEdit = (e) => {
        e.preventDefault();
        if (!editingUser) return;

        editForm.patch(route('admin.users.update', editingUser.user_login), {
            preserveScroll: true,
            onSuccess: () => closeEdit(),
        });
    };

    const openPassword = (user) => {
        setPasswordUser(user);
        passwordForm.reset();
    };

    const closePassword = () => {
        setPasswordUser(null);
        passwordForm.reset();
    };

    const submitPassword = (e) => {
        e.preventDefault();
        if (!passwordUser) return;

        passwordForm.patch(route('admin.users.password', passwordUser.user_login), {
            preserveScroll: true,
            onSuccess: () => closePassword(),
        });
    };

    const openDelete = (user) => {
        setDeletingUser(user);
    };

    const closeDelete = () => {
        setDeletingUser(null);
        deleteForm.reset();
    };

    const submitDelete = (e) => {
        e.preventDefault();
        if (!deletingUser) return;

        deleteForm.delete(route('admin.users.destroy', deletingUser.user_login), {
            preserveScroll: true,
            onSuccess: () => closeDelete(),
        });
    };

    const toggleActive = (user) => {
        if (user.user_login === currentLogin) {
            return;
        }

        router.patch(
            route('admin.users.update', user.user_login),
            {
                employee_name: user.employee_name || '',
                role: user.role || roles[0] || 'rd',
                is_active: !user.is_active,
            },
            { preserveScroll: true }
        );
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Account Management</h2>}
        >
            <Head title="Account Management" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900">Create User</h3>
                        <form onSubmit={submitCreate} className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="employee_name" value="Employee Name" />
                                <TextInput
                                    id="employee_name"
                                    name="employee_name"
                                    className="mt-1 block w-full"
                                    value={createForm.data.employee_name}
                                    onChange={(e) => createForm.setData('employee_name', e.target.value)}
                                    required
                                />
                                <InputError className="mt-2" message={createForm.errors.employee_name} />
                            </div>

                            <div>
                                <InputLabel htmlFor="user_login" value="User Login" />
                                <TextInput
                                    id="user_login"
                                    name="user_login"
                                    className="mt-1 block w-full"
                                    value={createForm.data.user_login}
                                    onChange={(e) => createForm.setData('user_login', e.target.value)}
                                    required
                                />
                                <InputError className="mt-2" message={createForm.errors.user_login} />
                            </div>

                            <div>
                                <InputLabel htmlFor="role" value="Role" />
                                <select
                                    id="role"
                                    name="role"
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={createForm.data.role}
                                    onChange={(e) => createForm.setData('role', e.target.value)}
                                >
                                    {roles.map((role) => (
                                        <option key={role} value={role}>
                                            {formatRoleLabel(role)}
                                        </option>
                                    ))}
                                </select>
                                <InputError className="mt-2" message={createForm.errors.role} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    checked={createForm.data.is_active}
                                    onChange={(e) => createForm.setData('is_active', e.target.checked)}
                                />
                                <InputLabel htmlFor="is_active" value="Active" />
                                <InputError className="mt-2" message={createForm.errors.is_active} />
                            </div>

                            <div>
                                <InputLabel htmlFor="password" value="Password" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    className="mt-1 block w-full"
                                    value={createForm.data.password}
                                    onChange={(e) => createForm.setData('password', e.target.value)}
                                    required
                                />
                                <InputError className="mt-2" message={createForm.errors.password} />
                            </div>

                            <div>
                                <InputLabel htmlFor="password_confirmation" value="Confirm Password" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    className="mt-1 block w-full"
                                    value={createForm.data.password_confirmation}
                                    onChange={(e) => createForm.setData('password_confirmation', e.target.value)}
                                    required
                                />
                                <InputError className="mt-2" message={createForm.errors.password_confirmation} />
                            </div>

                            <div className="md:col-span-2 flex justify-end">
                                <PrimaryButton disabled={createForm.processing}>Create User</PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <div className="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900">Users</h3>
                        <div className="mt-6 overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Login</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {users.length === 0 && (
                                        <tr>
                                            <td className="px-4 py-4 text-sm text-gray-500" colSpan="5">
                                                No users found.
                                            </td>
                                        </tr>
                                    )}
                                    {users.map((user) => {
                                        const isSelf = user.user_login === currentLogin;

                                        return (
                                        <tr key={user.user_login}>
                                            <td className="px-4 py-4 text-sm text-gray-900">{user.user_login}</td>
                                            <td className="px-4 py-4 text-sm text-gray-900">{user.employee_name || '-'}</td>
                                            <td className="px-4 py-4 text-sm text-gray-900">{formatRoleLabel(user.role)}</td>
                                            <td className="px-4 py-4 text-sm">
                                                <span
                                                    className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                                        user.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'
                                                    }`}
                                                >
                                                    {user.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-right space-x-2">
                                                <SecondaryButton type="button" onClick={() => openEdit(user)}>
                                                    Edit
                                                </SecondaryButton>
                                                <SecondaryButton type="button" onClick={() => openPassword(user)}>
                                                    Set Password
                                                </SecondaryButton>
                                                {user.is_active ? (
                                                    <SecondaryButton type="button" onClick={() => toggleActive(user)} disabled={isSelf}>
                                                        Deactivate
                                                    </SecondaryButton>
                                                ) : (
                                                    <SuccessButton type="button" onClick={() => toggleActive(user)} disabled={isSelf}>
                                                        Activate
                                                    </SuccessButton>
                                                )}
                                                <DangerButton type="button" onClick={() => openDelete(user)} disabled={isSelf}>
                                                    Delete
                                                </DangerButton>
                                            </td>
                                        </tr>
                                    )})}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={Boolean(editingUser)} onClose={closeEdit}>
                <form onSubmit={submitEdit} className="p-6 space-y-6">
                    <h2 className="text-lg font-medium text-gray-900">Edit User</h2>
                    <div>
                        <InputLabel htmlFor="edit_employee_name" value="Employee Name" />
                        <TextInput
                            id="edit_employee_name"
                            name="employee_name"
                            className="mt-1 block w-full"
                            value={editForm.data.employee_name}
                            onChange={(e) => editForm.setData('employee_name', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={editForm.errors.employee_name} />
                    </div>

                    <div>
                        <InputLabel htmlFor="edit_role" value="Role" />
                        <select
                            id="edit_role"
                            name="role"
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            value={editForm.data.role}
                            onChange={(e) => editForm.setData('role', e.target.value)}
                            disabled={editingUser?.user_login === currentLogin}
                        >
                            {roles.map((role) => (
                                <option key={role} value={role}>
                                    {formatRoleLabel(role)}
                                </option>
                            ))}
                        </select>
                        <InputError className="mt-2" message={editForm.errors.role} />
                    </div>

                    <div className="flex items-center space-x-3">
                        <Checkbox
                            id="edit_is_active"
                            name="is_active"
                            checked={editForm.data.is_active}
                            onChange={(e) => editForm.setData('is_active', e.target.checked)}
                            disabled={editingUser?.user_login === currentLogin}
                        />
                        <InputLabel htmlFor="edit_is_active" value="Active" />
                        <InputError className="mt-2" message={editForm.errors.is_active} />
                    </div>

                    <div className="flex justify-end space-x-3">
                        <SecondaryButton type="button" onClick={closeEdit}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton disabled={editForm.processing}>Save</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={Boolean(passwordUser)} onClose={closePassword}>
                <form onSubmit={submitPassword} className="p-6 space-y-6">
                    <h2 className="text-lg font-medium text-gray-900">Set Password</h2>
                    <div>
                        <InputLabel htmlFor="new_password" value="Password" />
                        <TextInput
                            id="new_password"
                            type="password"
                            name="password"
                            className="mt-1 block w-full"
                            value={passwordForm.data.password}
                            onChange={(e) => passwordForm.setData('password', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={passwordForm.errors.password} />
                    </div>
                    <div>
                        <InputLabel htmlFor="new_password_confirmation" value="Confirm Password" />
                        <TextInput
                            id="new_password_confirmation"
                            type="password"
                            name="password_confirmation"
                            className="mt-1 block w-full"
                            value={passwordForm.data.password_confirmation}
                            onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={passwordForm.errors.password_confirmation} />
                    </div>
                    <div className="flex justify-end space-x-3">
                        <SecondaryButton type="button" onClick={closePassword}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton disabled={passwordForm.processing}>Update</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={Boolean(deletingUser)} onClose={closeDelete}>
                <form onSubmit={submitDelete} className="p-6 space-y-6">
                    <h2 className="text-lg font-medium text-gray-900">Delete User</h2>
                    <p className="text-sm text-gray-600">
                        Are you sure you want to delete {deletingUser?.user_login}? This action cannot be undone.
                    </p>
                    <InputError className="mt-2" message={deleteForm.errors.user_login} />
                    <div className="flex justify-end space-x-3">
                        <SecondaryButton type="button" onClick={closeDelete}>
                            Cancel
                        </SecondaryButton>
                        <DangerButton disabled={deleteForm.processing}>Delete</DangerButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
