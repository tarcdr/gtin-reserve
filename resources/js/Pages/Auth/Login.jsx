import { useEffect } from 'react';
import Checkbox from '@/Components/Checkbox';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        user_login: '',
        pass_login: '',
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset('pass_login');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();

        post(route('login'));
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            {status && <div className="mb-4 font-medium text-sm text-green-600">{status}</div>}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="user_login" value="User Name" />

                    <TextInput
                        id="user_login"
                        name="user_login"
                        value={data.user_login}
                        className="mt-1 block w-full"
                        autoComplete="user_login"
                        isFocused={true}
                        onChange={(e) => setData('user_login', e.target.value)}
                    />

                    <InputError message={errors.user_login} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="pass_login" value="Password" />

                    <TextInput
                        id="pass_login"
                        type="password"
                        name="pass_login"
                        value={data.pass_login}
                        className="mt-1 block w-full"
                        autoComplete="current-pass_login"
                        onChange={(e) => setData('pass_login', e.target.value)}
                    />

                    <InputError message={errors.pass_login} className="mt-2" />
                </div>

                <div className="block mt-4">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                        />
                        <span className="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <div className="flex columns-2 items-center">
                    <Link
                        href={route('register')}
                        className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Register
                    </Link>
                    <div className="w-full flex items-center justify-end">
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Forgot your pass_login?
                            </Link>
                        )}

                        <PrimaryButton className="ml-4" disabled={processing}>
                            Log in
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </GuestLayout>
    );
}
