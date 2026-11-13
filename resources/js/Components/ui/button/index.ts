import { cva, type VariantProps } from 'class-variance-authority';

export const buttonVariants = cva(
    'inline-flex items-center justify-center whitespace-nowrap rounded-lg text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 select-none min-h-[44px] min-w-[44px]',
    {
        variants: {
            variant: {
                default: 'bg-teal-600 text-white shadow hover:bg-teal-700 active:bg-teal-800',
                destructive: 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 active:bg-rose-800',
                outline: 'border border-slate-300 bg-white shadow-sm hover:bg-slate-50 hover:text-slate-900',
                secondary: 'bg-slate-100 text-slate-900 shadow-sm hover:bg-slate-200',
                ghost: 'hover:bg-slate-100 hover:text-slate-900',
                link: 'text-teal-600 underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-11 px-4 py-2',
                sm: 'h-11 rounded-md px-3 text-xs',
                lg: 'h-12 rounded-lg px-8 text-base',
                icon: 'h-11 w-11 p-0',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
);

export type ButtonVariants = VariantProps<typeof buttonVariants>;
