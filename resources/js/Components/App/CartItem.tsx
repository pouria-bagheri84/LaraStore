import React, {useState} from "react";
import {Link, router, useForm} from "@inertiajs/react";
import {CartItem as CartItemType} from "@/types";
import TextInput from "@/Components/Core/TextInput";
import CurrencyFormatter from "@/Components/Core/CurrencyFormatter";
import {productRoutes} from "@/helpers";
import {Simulate} from "react-dom/test-utils";
import error = Simulate.error;


function CartItem({item}: {item: CartItemType}) {

    const deleteForm = useForm({
        option_ids: item.option_ids
    })

    const [error, setError] = useState('')

    const onDeleteClick = () => {
        deleteForm.delete(route('cart.destroy', item.product_id), {
            preserveScroll: true
        })
    }

    const handleQuantityChange = (ev: React.ChangeEvent<HTMLInputElement>) => {
        setError('')
        router.put(route('cart.update', item.product_id), {
            quantity: ev.target.value,
            option_ids: item.option_ids
        }, {
            preserveScroll: true,
            onError: (errors) => {
                setError(Object.values(errors)[0])
            }
        })
    }

    return (
        <>
            <div key={item.id} className={'flex gap-6 p-3'}>
                <Link href={productRoutes(item)}
                      className={'w-32 min-w-16 sm:min-w-32 min-h-16 sm:min-h-32 flex justify-center self-start'}
                >
                    <img src={item.image} alt="" className={'max-h-full max-w-full'}/>
                </Link>
                <div className={'flex-1 flex flex-col'}>
                    <div className={'flex-1'}>
                        <h3 className={'mb-3 text-sm font-semibold'}>
                            <Link href={productRoutes(item)}>
                                {item.title}
                            </Link>
                        </h3>

                        <div className={'text-xs'}>
                            {item.options.map(option => (
                                <div key={option.id}>
                                    <strong className={'text-bold'}>{option.type.name}:</strong>
                                    {option.name}
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className={'flex justify-between items-center mt-4'}>
                        <div className={'flex gap-2 items-center'}>
                            <div className={'text-sm'}>Quantity:</div>
                            <div className={error ? 'tooltip tooltip-open tooltip-error' : ''} data-tip={error}>
                                <TextInput type={'number'}
                                           defaultValue={item.quantity}
                                           onBlur={handleQuantityChange}
                                           className={'input-sm w-16'}
                                />
                                <button onClick={()=> onDeleteClick()} className={'btn btn-sm btn-ghost'}>
                                    Delete
                                </button>
                                <button className={'btn btn-sm btn-ghost'}>
                                    Save for Later
                                </button>
                            </div>

                            <div className={'font-bold text-lg'}>
                                <CurrencyFormatter amount={item.price * item.quantity}/>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div className={'divider'}></div>
        </>
    );
}

export default CartItem;