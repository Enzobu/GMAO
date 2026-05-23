import * as React from "react"
import { ChevronDown } from "lucide-react"

import { cn } from "@/lib/utils"

export interface NativeSelectOption {
  value: string
  label: string
}

type NativeSelectProps = Omit<React.ComponentProps<"select">, "children"> & {
  label?: string
  options: readonly NativeSelectOption[]
  placeholder?: string
  wrapperClassName?: string
}

function NativeSelect({
  label,
  options,
  placeholder,
  className,
  wrapperClassName,
  ...props
}: NativeSelectProps) {
  const select = (
    <span className="relative min-w-0">
      <select
        className={cn(
          "h-8 w-full min-w-0 appearance-none rounded-lg border border-input bg-background py-1 pr-9 pl-2.5 text-sm outline-none transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:bg-input/50 disabled:opacity-50",
          className
        )}
        {...props}
      >
        {placeholder !== undefined && <option value="">{placeholder}</option>}
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      <ChevronDown className="pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
    </span>
  )

  if (!label) {
    return select
  }

  return (
    <label className={cn("grid min-w-0 gap-1.5 text-sm font-medium", wrapperClassName)}>
      <span>{label}</span>
      {select}
    </label>
  )
}

export { NativeSelect }
