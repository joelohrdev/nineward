import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-semibold transition-colors duration-150 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clay aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-ink text-paper hover:bg-ink/85",
        destructive:
          "bg-danger text-sheet hover:bg-danger/90",
        outline:
          "border border-ink/80 bg-transparent text-ink hover:bg-wash",
        secondary:
          "border border-rule-strong bg-sheet text-ink hover:bg-wash",
        ghost: "text-ink hover:bg-wash",
        link: "text-ink underline decoration-clay decoration-2 underline-offset-4 hover:decoration-ink",
        brand: "bg-brand text-brand-ink hover:bg-brand/90",
      },
      size: {
        default: "h-10 px-4 py-2 has-[>svg]:px-3",
        sm: "h-8 px-3 text-[0.8125rem] has-[>svg]:px-2.5",
        lg: "h-12 px-6 text-base has-[>svg]:px-4",
        icon: "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
