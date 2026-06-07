import { useEffect } from 'react';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import logo from '../../../assets/images/logo.png';
import loginImage from '../../../assets/images/login_image.png';

export default function Login({ status, canResetPassword, connection = '' }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        user_login: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();

        post(route('login'));
    };

    return (
        <div className="min-h-screen overflow-hidden bg-[#e9eff5]">
            <Head title="Log in" />

            <div className="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_26rem] xl:grid-cols-[minmax(0,1fr)_30rem]">
                <div className="relative hidden overflow-hidden lg:block">
                    <img
                        src={loginImage}
                        alt="Login illustration"
                        className="absolute inset-0 h-full w-full object-contain object-center"
                    />
                </div>

                <div className="flex items-center justify-center px-4 py-8 sm:px-8 lg:px-10">
                    <div className="w-full max-w-[24rem] rounded-[1.25rem] bg-white px-6 py-8 shadow-[0_16px_42px_rgba(15,23,42,0.12)] sm:px-8 sm:py-10">
                        <div className="flex justify-center">
                            <img
                                src={logo}
                                alt="Do Day Dream"
                                className="h-24 w-24 object-contain sm:h-28 sm:w-28"
                            />
                        </div>

                        {status && <div className="mt-6 font-medium text-sm text-green-600">{status}</div>}

                        <form onSubmit={submit} className="mt-6">
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
                                <InputLabel htmlFor="password" value="Password" />

                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                />

                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div className="mt-4 block">
                                <label className="flex items-center">
                                    <Checkbox
                                        name="remember"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                    />
                                    <span className="ml-2 text-sm text-gray-600">Remember me</span>
                                </label>
                            </div>

                            <div className="mt-5 flex items-center justify-end">
                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        Forgot your password?
                                    </Link>
                                )}

                                <PrimaryButton className="ml-4" disabled={processing}>
                                    Log in
                                </PrimaryButton>
                            </div>
                        </form>

                        <div className="mt-8 text-center text-sm text-gray-500">
                            {connection}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
