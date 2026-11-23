import { useId, useMemo, useState } from "react"
import { Popover as PopoverPrimitive } from "radix-ui"
import { Check, ChevronDown, Search } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { cn } from "@/lib/utils"

export interface FilterSelectOption {
  value: string
  label: string
}

interface FilterSelectProps {
  label: string
  value: string
  options: readonly FilterSelectOption[]
  onValueChange: (value: string) => void
  disabled?: boolean
}

export function FilterSelect({
  label,
  value,
  options,
  onValueChange,
  disabled,
}: Readonly<FilterSelectProps>) {
  const [open, setOpen] = useState(false)
  const [query, setQuery] = useState("")
  const labelId = useId()
  const selected = options.find((option) => option.value === value)
  const filteredOptions = useMemo(() => {
    const search = query.trim().toLocaleLowerCase("fr")
    return options.filter((option) => (
      option.label.toLocaleLowerCase("fr").includes(search)
    ))
  }, [options, query])

  function changeOpen(next: boolean) {
    setOpen(next)
    if (!next) setQuery("")
  }

  function selectOption(nextValue: string) {
    onValueChange(nextValue)
    changeOpen(false)
  }

  return (
    <div className="grid min-w-0 gap-1.5 text-sm font-medium">
      <span id={labelId}>{label}</span>
      <PopoverPrimitive.Root open={open} onOpenChange={changeOpen}>
        <PopoverPrimitive.Trigger asChild>
          <Button
            type="button"
            variant="outline"
            aria-labelledby={labelId}
            aria-expanded={open}
            disabled={disabled}
            className="h-8 w-full min-w-0 justify-between px-2.5 font-normal"
          >
            <span className="truncate">{selected?.label ?? "Sélectionner"}</span>
            <ChevronDown className="ml-2 size-4 text-muted-foreground" />
          </Button>
        </PopoverPrimitive.Trigger>
        <PopoverPrimitive.Portal>
          <PopoverPrimitive.Content
            align="start"
            sideOffset={4}
            className={cn(
              "z-50 w-(--radix-popover-trigger-width) rounded-lg border",
              "bg-popover p-1 text-popover-foreground shadow-md",
            )}
          >
            {options.length > 6 && (
              <div className="relative mb-1">
                <Search className="pointer-events-none absolute top-2 left-2 size-4 text-muted-foreground" />
                <Input
                  autoFocus
                  aria-label={`Rechercher dans ${label.toLocaleLowerCase("fr")}`}
                  className="pl-8"
                  placeholder="Rechercher..."
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                />
              </div>
            )}
            <div className="max-h-60 space-y-0.5 overflow-y-auto">
              {filteredOptions.map((option) => (
                <Button
                  key={option.value}
                  type="button"
                  variant="ghost"
                  className="h-8 w-full justify-between gap-2 px-2 text-left font-normal"
                  onClick={() => selectOption(option.value)}
                >
                  <span className="min-w-0 truncate">{option.label}</span>
                  {option.value === value && <Check className="size-4 shrink-0" />}
                </Button>
              ))}
              {filteredOptions.length === 0 && (
                <p className="px-2 py-3 text-center text-xs text-muted-foreground">
                  Aucun résultat.
                </p>
              )}
            </div>
          </PopoverPrimitive.Content>
        </PopoverPrimitive.Portal>
      </PopoverPrimitive.Root>
    </div>
  )
}
