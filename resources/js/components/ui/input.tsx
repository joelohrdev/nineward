import * as React from "react"

import { cn } from "@/lib/utils"

function Input({ className, type, ...props }: React.ComponentProps<"input">) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        "border-rule border-b-rule-strong file:text-foreground placeholder:text-ink-muted/70 flex h-11 w-full min-w-0 rounded-sm border border-b-2 bg-sheet px-3 py-1 text-base transition-colors outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:h-10 md:text-[0.9375rem]",
        "focus-visible:border-b-clay focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clay",
        "aria-invalid:border-b-danger",
        className
      )}
      {...props}
    />
  )
}

export { Input }
