import {Head, Link} from "@inertiajs/react";
import CurrencyFormatter from "@/Components/Core/CurrencyFormatter";
import {XCircleIcon} from "@heroicons/react/24/outline";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {PageProps, Order} from "@/types";

function Failure({orders}: PageProps<{ orders: Order[] }>) {
    return (
        <AuthenticatedLayout>
            <Head title={'Payment was Failed'}/>

            <div className={'w-[360px] sm:w-[480px] mx-auto py-8 px-4'}>
                <div className={'flex flex-col gap-2 items-center'}>
                    <div className={'text-6xl text-red-600'}>
                        <XCircleIcon className={'size-24'}/>
                    </div>
                    <div className={'text-3xl'}>
                        Payment was Failed
                    </div>
                </div>
                <div className={'my-6 text-lg'}>
                    Sorry your payment has been failed. Try again for a few minute later.
                </div>

                <div className={'flex justify-between mt-4'}>
                    <Link href={'#'} className={'btn btn-primary'}>
                        View Order Details
                    </Link>
                    <Link href={route('dashboard')} className={'btn'}>
                        Back to Home
                    </Link>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}

export default Failure;