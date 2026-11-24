import { useState } from "react"

import { updatePartQuantity } from "@/api/parts"
import { LabelText } from "@/components/page-primitives"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import type { Part } from "@/types/part"
import { partName } from "@/lib/part-utils"

export function AddStockDialog({
  part,
  onOpenChange,
  onSaved,
}: Readonly<{
  part: Part | null
  onOpenChange: (open: boolean) => void
  onSaved: (part: Part) => void
}>) {
  const [quantity, setQuantity] = useState("1")
  const [isSaving, setIsSaving] = useState(false)

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!part) {
      return
    }

    const quantityToAdd = Number(quantity)

    if (!Number.isFinite(quantityToAdd) || quantityToAdd <= 0) {
      return
    }

    setIsSaving(true)

    try {
      const saved = await updatePartQuantity(
        part.id,
        part.quantity + quantityToAdd,
      )
      onSaved(saved)
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <Dialog
      open={part !== null}
      onOpenChange={(open) => !isSaving && onOpenChange(open)}
    >
      <DialogContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <DialogHeader>
            <DialogTitle>Ajouter du stock</DialogTitle>
            <DialogDescription>
              {part ? `Stock actuel ${partName(part)} : ${part.quantity}` : ""}
            </DialogDescription>
          </DialogHeader>
          <label className="grid gap-1.5 text-sm font-medium">
            <LabelText label="Nombre de pièces à ajouter" required />
            <Input
              type="number"
              min="1"
              step="1"
              value={quantity}
              onChange={(event) => setQuantity(event.target.value)}
              required
            />
          </label>
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isSaving}
            >
              Annuler
            </Button>
            <Button type="submit" disabled={isSaving}>
              {isSaving ? "Ajout..." : "Ajouter"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

