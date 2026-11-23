import { useMemo, useState } from "react"
import { Minus, Plus, Search, Trash2 } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import type { Part } from "@/types/part"

export interface SelectedStockPart {
  id?: number
  partId: string
  quantity: string
  notes: string
}

interface Props {
  parts: Part[]
  selected: SelectedStockPart[]
  previouslyConsumed: Record<string, number>
  disabled: boolean
  onChange: (value: SelectedStockPart[]) => void
}

export function StockPartPicker({
  parts,
  selected,
  previouslyConsumed,
  disabled,
  onChange,
}: Readonly<Props>) {
  const [search, setSearch] = useState("")
  const selectedIds = useMemo(
    () => new Set(selected.map((item) => item.partId)),
    [selected],
  )
  const matches = useMemo(() => parts.filter((part) => (
    part.quantity > 0
    && !part.isDeleted
    && !selectedIds.has(String(part.id))
    && part.partType.name.toLocaleLowerCase("fr").includes(
      search.trim().toLocaleLowerCase("fr"),
    )
  )), [parts, search, selectedIds])

  function updateQuantity(index: number, value: string) {
    const line = selected[index]
    const part = parts.find((item) => String(item.id) === line.partId)
    const max = (part?.quantity ?? 0) + (previouslyConsumed[line.partId] ?? 0)
    const next = Number(value)
    const quantity = value === ""
      ? ""
      : String(Math.max(1, Math.min(max, Number.isFinite(next) ? next : 1)))

    onChange(selected.map((item, i) => (
      i === index ? { ...item, quantity } : item
    )))
  }

  function addPart(part: Part) {
    onChange([...selected, {
      partId: String(part.id),
      quantity: "1",
      notes: "",
    }])
    setSearch("")
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <span className="text-sm font-medium text-muted-foreground">
          Sélection
        </span>
        <span className="rounded-md bg-muted px-2 py-1 text-xs text-muted-foreground">
          {selected.length} pièce{selected.length > 1 ? "s" : ""}
        </span>
      </div>

      {selected.length > 0 && (
        <div className="divide-y overflow-hidden rounded-lg border">
          {selected.map((line, index) => {
            const part = parts.find((item) => String(item.id) === line.partId)
            const consumed = previouslyConsumed[line.partId] ?? 0
            const max = (part?.quantity ?? 0) + consumed
            const quantity = Number(line.quantity)

            return (
              <div
                key={line.partId}
                className="flex flex-wrap items-center gap-3 px-3 py-3 sm:flex-nowrap"
              >
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-medium">
                    {part?.partType.name ?? "Pièce indisponible"}
                  </p>
                  <div className="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                    <span>{part?.quantity ?? 0} disponible(s)</span>
                    {consumed > 0 && <span>{consumed} déjà utilisée(s)</span>}
                  </div>
                </div>

                <div className="flex items-center gap-2">
                  <div
                    className="flex h-8 items-center rounded-lg border bg-background"
                    role="group"
                    aria-label={`Quantité de ${part?.partType.name ?? "pièce"}`}
                  >
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon-sm"
                      className="rounded-r-none"
                      aria-label="Diminuer la quantité"
                      disabled={disabled || !Number.isFinite(quantity) || quantity <= 1}
                      onClick={() => updateQuantity(index, String(quantity - 1))}
                    >
                      <Minus className="size-3.5" />
                    </Button>
                    <Input
                      type="number"
                      min={1}
                      max={max}
                      step={1}
                      aria-label={`Quantité de ${part?.partType.name ?? "pièce"}`}
                      className="h-7 w-12 rounded-none border-0 bg-transparent px-1 text-center text-sm tabular-nums shadow-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                      value={line.quantity}
                      disabled={disabled}
                      onChange={(event) => updateQuantity(index, event.target.value)}
                    />
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon-sm"
                      className="rounded-l-none"
                      aria-label="Augmenter la quantité"
                      disabled={disabled || !Number.isFinite(quantity) || quantity >= max}
                      onClick={() => updateQuantity(index, String(quantity + 1))}
                    >
                      <Plus className="size-3.5" />
                    </Button>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    className="text-muted-foreground hover:text-destructive"
                    disabled={disabled}
                    aria-label={`Retirer ${part?.partType.name ?? "la pièce"}`}
                    onClick={() => onChange(selected.filter((_, i) => i !== index))}
                  >
                    <Trash2 className="size-4" />
                  </Button>
                </div>
              </div>
            )
          })}
        </div>
      )}

      <div className="space-y-3">
        <label className="block text-sm font-medium" htmlFor="stock-part-search">
          Ajouter une pièce
        </label>
        <div className="relative">
          <Search className="pointer-events-none absolute left-3 top-2.5 size-4 text-muted-foreground" />
          <Input
            id="stock-part-search"
            className="pl-9"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Rechercher dans le stock..."
            autoComplete="off"
            disabled={disabled}
            aria-controls="stock-part-results"
          />
        </div>
        <div
          id="stock-part-results"
          className="max-h-56 overflow-y-auto rounded-lg border p-1"
        >
          {matches.map((part) => (
            <Button
              key={part.id}
              type="button"
              variant="ghost"
              className="flex h-auto w-full justify-between gap-3 px-3 py-2 text-left"
              disabled={disabled}
              onClick={() => addPart(part)}
            >
              <span className="min-w-0 truncate text-sm">{part.partType.name}</span>
              <span className="flex shrink-0 items-center gap-2 text-xs text-muted-foreground">
                {part.quantity} en stock <Plus className="size-3.5" />
              </span>
            </Button>
          ))}
          {matches.length === 0 && (
            <p className="px-3 py-2 text-sm text-muted-foreground">
              Aucune autre pièce disponible.
            </p>
          )}
        </div>
      </div>
    </div>
  )
}
