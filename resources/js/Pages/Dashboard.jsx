import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import parse from 'html-react-parser';

export default function Dashboard({ auth, message = '' }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="my-12 flex justify-center">
                            <Link href={route('product.new')}>
                                <PrimaryButton className="ml-3 font-bold py-4 px-8">
                                  <span className="text-3xl">
                                    NPD
                                  </span>
                                </PrimaryButton>
                            </Link>
                            <PrimaryButton className="ml-3 font-bold py-4 px-8">
                              <span className="text-3xl">
                                Existing
                              </span>
                            </PrimaryButton>
                        </div>

                        <div className="p-6 text-gray-900">{parse(message)}</div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
