import { useMemo, useState } from "react"
import { Plus, Trash2 } from "lucide-react"

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

  return (
    <div className="space-y-4">
      {selected.map((line, index) => {
        const part = parts.find((item) => String(item.id) === line.partId)
        const max = (part?.quantity ?? 0) + (previouslyConsumed[line.partId] ?? 0)

        return (
          <div key={line.partId} className="flex flex-wrap items-end gap-3 rounded-lg border p-3">
            <div className="min-w-40 flex-1">
              <div className="font-medium">{part?.partType.name ?? "Pièce indisponible"}</div>
              <div className="text-xs text-muted-foreground">
                Disponible : {part?.quantity ?? 0}
                {previouslyConsumed[line.partId]
                  ? ` (+${previouslyConsumed[line.partId]} déjà consommée(s))`
                  : ""}
              </div>
            </div>
            <label className="grid gap-1 text-sm">
              Quantité
              <Input
                type="number"
                className="w-24"
                min={1}
                max={max}
                step={1}
                value={line.quantity}
                disabled={disabled}
                onChange={(event) => updateQuantity(index, event.target.value)}
              />
            </label>
            <Button
              type="button"
              variant="outline"
              size="icon"
              disabled={disabled}
              aria-label="Retirer la pièce"
              onClick={() => onChange(selected.filter((_, i) => i !== index))}
            >
              <Trash2 />
            </Button>
          </div>
        )
      })}

      <div className="space-y-2">
        <label className="text-sm font-medium" htmlFor="stock-part-search">
          Rechercher une pièce disponible
        </label>
        <Input
          id="stock-part-search"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder="Ex. bougies, filtre à huile..."
          disabled={disabled}
        />
        <div className="max-h-56 space-y-1 overflow-y-auto">
          {matches.map((part) => (
            <Button
              key={part.id}
              type="button"
              variant="ghost"
              className="flex h-auto w-full justify-between gap-3 py-2 text-left"
              disabled={disabled}
              onClick={() => {
                onChange([...selected, {
                  partId: String(part.id),
                  quantity: "1",
                  notes: "",
                }])
                setSearch("")
              }}
            >
              <span className="min-w-0 truncate">{part.partType.name}</span>
              <span className="flex shrink-0 items-center gap-2 text-muted-foreground">
                {part.quantity} en stock <Plus className="size-4" />
              </span>
            </Button>
          ))}
          {matches.length === 0 && (
            <p className="p-3 text-sm text-muted-foreground">
              Aucune autre pièce disponible.
            </p>
          )}
        </div>
      </div>
    </div>
  )
}
