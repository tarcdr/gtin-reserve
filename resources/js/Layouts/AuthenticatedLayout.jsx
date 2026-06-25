import { useEffect, useState } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import Modal from '@/Components/Modal';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import PageIdentity from '@/Components/PageIdentity';
import SecondaryButton from '@/Components/SecondaryButton';
import { Link, usePage } from '@inertiajs/react';

export default function Authenticated({ user, header, pageIdentity = null, children }) {
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const [databaseError, setDatabaseError] = useState('');
    const { flash } = usePage().props;
    const isAdmin = user?.role === 'admin';
    const roleLabel = user?.role ? user.role.toUpperCase() : 'USER';

    useEffect(() => {
        const nextError = flash?.error || '';
        if (nextError) {
            setDatabaseError(nextError);
        }
    }, [flash?.error]);

    return (
        <div className="min-h-screen bg-gray-100 text-green-900 font-extrabold">
            <nav className="bg-white border-b border-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex">
                            <div className="shrink-0 flex items-center">
                                <Link href="/dashboard">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                    className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md px-2"
                                >
                                    Dashboard
                                </NavLink>

                                <div className="hidden sm:flex sm:items-center sm:ml-6">
                                    <div className="ml-3 relative">
                                        <Dropdown>
                                            <Dropdown.Trigger>
                                                <span className="rounded-md">
                                                    <button
                                                        type="button"
                                                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-extrabold rounded-md text-gray-500 bg-white hover:bg-green-50 hover:text-green-800 focus:outline-none transition ease-in-out duration-150 text-green-900 font-extrabold"
                                                    >
                                                        Product(FG)-Report

                                                        <svg
                                                            className="ml-2 -mr-0.5 h-4 w-4"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fillRule="evenodd"
                                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                                clipRule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </Dropdown.Trigger>

                                            <Dropdown.Content>
                                                {/* <Dropdown.Link href={route('material.request')}>
                                                    Material Request
                                                </Dropdown.Link> */}
                                                <Dropdown.Link href={route('material.report')} className="text-green-900 font-extrabold hover:bg-green-50">
                                                    Material Confirm/Report
                                                </Dropdown.Link>
                                            </Dropdown.Content>
                                        </Dropdown>
                                    </div>
                                </div>
                                <div className="hidden sm:flex sm:items-center sm:ml-6">
                                    <div className="ml-3 relative">
                                        <Dropdown>
                                            <Dropdown.Trigger>
                                                <span className="rounded-md">
                                                    <button
                                                        type="button"
                                                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-extrabold rounded-md text-gray-500 bg-white hover:bg-green-50 hover:text-green-800 focus:outline-none transition ease-in-out duration-150 text-green-900 font-extrabold"
                                                    >
                                                        Business Supply

                                                        <svg
                                                            className="ml-2 -mr-0.5 h-4 w-4"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fillRule="evenodd"
                                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                                clipRule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </Dropdown.Trigger>

                                            <Dropdown.Content>
                                                <Dropdown.Link href={route('business-supply.new')} className="text-green-900 font-extrabold hover:bg-green-50">
                                                    NEW BUSINESS SUPPLY
                                                </Dropdown.Link>
                                                <Dropdown.Link href={route('business-supply.existing')} className="text-green-900 font-extrabold hover:bg-green-50">
                                                    EXISTING BUSINESS SUPPLY
                                                </Dropdown.Link>
                                            </Dropdown.Content>
                                        </Dropdown>
                                    </div>
                                </div>
                                <div className="hidden sm:flex sm:items-center sm:ml-6">
                                    <div className="ml-3 relative">
                                        <Dropdown>
                                            <Dropdown.Trigger>
                                                <span className="rounded-md">
                                                    <button
                                                        type="button"
                                                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-extrabold rounded-md text-gray-500 bg-white hover:bg-green-50 hover:text-green-800 focus:outline-none transition ease-in-out duration-150 text-green-900 font-extrabold"
                                                    >
                                                        GTIN

                                                        <svg
                                                            className="ml-2 -mr-0.5 h-4 w-4"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fillRule="evenodd"
                                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                                clipRule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </Dropdown.Trigger>

                                            <Dropdown.Content>
                                                <Dropdown.Link href={route('request')} className="text-green-900 font-extrabold hover:bg-green-50">
                                                    Request
                                                </Dropdown.Link>
                                                <Dropdown.Link href={route('report')} className="text-green-900 font-extrabold hover:bg-green-50">
                                                    Confirm/Report
                                                </Dropdown.Link>
                                            </Dropdown.Content>
                                        </Dropdown>
                                    </div>
                                </div>
                                <NavLink
                                    href={route('rm.report')}
                                    active={route().current('rm.report')}
                                    className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md px-2"
                                >
                                    Export-to-SAP
                                </NavLink>
                                {isAdmin && (
                                    <NavLink
                                        href={route('admin.users.index')}
                                        active={route().current('admin.users.index')}
                                        className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md px-2"
                                    >
                                        Account Management
                                    </NavLink>
                                )}
                            </div>
                        </div>

                        <div className="hidden sm:flex sm:items-center sm:ml-6">
                            <div className="ml-3 relative">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-extrabold rounded-md text-gray-500 bg-white hover:bg-green-50 hover:text-green-800 focus:outline-none transition ease-in-out duration-150 text-green-900 font-extrabold"
                                            >
                                                {`${user?.employee_name || '-'} (${roleLabel})`}

                                                <svg
                                                    className="ml-2 -mr-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')} className="text-green-900 font-extrabold hover:bg-green-50">
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link href={route('logout')} method="post" as="button" className="text-green-900 font-extrabold hover:bg-green-50">
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-mr-2 flex items-center sm:hidden">
                            <button
                                onClick={() => setShowingNavigationDropdown((previousState) => !previousState)}
                                className="inline-flex items-center justify-center p-2 rounded-md text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:outline-none focus:bg-green-50 focus:text-green-800 transition duration-150 ease-in-out"
                            >
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'}
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={showingNavigationDropdown ? 'inline-flex' : 'hidden'}
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div className={(showingNavigationDropdown ? 'block' : 'hidden') + ' sm:hidden'}>
                    <div className="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                            className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        {/* <ResponsiveNavLink href={route('material.request')} active={route().current('material.request')}>
                            Material/Request
                        </ResponsiveNavLink> */}
                        <ResponsiveNavLink
                            href={route('material.report')}
                            active={route().current('material.report')}
                            className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md"
                        >
                            Material_Confirm/Report
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('request')}
                            active={route().current('request')}
                            className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md"
                        >
                            GTIN/Request
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('business-supply.new')}
                            active={route().current('business-supply.new')}
                            className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md"
                        >
                            NEW BUSINESS SUPPLY
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('business-supply.existing')}
                            active={route().current('business-supply.existing')}
                            className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md"
                        >
                            EXISTING BUSINESS SUPPLY
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('report')}
                            active={route().current('report')}
                            className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md"
                        >
                            GTIN_Confirm/Report
                        </ResponsiveNavLink>
                        {isAdmin && (
                            <ResponsiveNavLink
                                href={route('admin.users.index')}
                                active={route().current('admin.users.index')}
                                className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md"
                            >
                                Account Management
                            </ResponsiveNavLink>
                        )}
                    </div>

                    <div className="pt-4 pb-1 border-t border-gray-200">
                        <div className="px-4">
                            <div className="text-base text-green-900 font-extrabold">{`${user?.employee_name || '-'} (${roleLabel})`}</div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink
                                href={route('profile.edit')}
                                className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md"
                            >
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                                className="text-green-900 font-extrabold hover:text-green-800 hover:bg-green-50 focus:text-green-800 focus:bg-green-50 rounded-md"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {(header || pageIdentity) && (
                <header className="bg-white shadow">
                    <div className="relative max-w-7xl mx-auto overflow-hidden py-6 px-4 sm:px-6 lg:px-8 space-y-4">
                        {header}
                        {pageIdentity && (
                            <PageIdentity
                                pageId={pageIdentity.pageId}
                            />
                        )}
                    </div>
                </header>
            )}

            <main>{children}</main>

            <Modal
                show={Boolean(databaseError)}
                onClose={() => setDatabaseError('')}
                maxWidth="lg"
            >
                <div className="p-6">
                    <h3 className="text-lg font-bold text-red-700">Database Error</h3>
                    <p className="mt-3 text-sm font-medium text-gray-700 whitespace-pre-wrap">
                        {databaseError}
                    </p>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton type="button" onClick={() => setDatabaseError('')}>
                            Close
                        </SecondaryButton>
                    </div>
                </div>
            </Modal>
        </div>
    );
}
