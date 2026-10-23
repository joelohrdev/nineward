import { cn } from "@/lib/utils"

function Skeleton({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="skeleton"
      className={cn("bg-ink/8 animate-pulse rounded-sm", className)}
      {...props}
    />
  )
}

export { Skeleton }
