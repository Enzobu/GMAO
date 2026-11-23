import { Link } from "react-router-dom"
import { Trash2 } from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import type { Part } from "@/types/part"
import { partName, stockStatus, vehicleDisplayName } from "@/lib/part-utils"

const LOW_STOCK_BADGE_CLASS =
  "border-amber-500/30 bg-amber-500/10 text-amber-700 " +
  "dark:text-amber-300"

export function StockTableRow({
  part,
  isAdmin,
  onAdd,
  onDelete,
}: Readonly<{
  part: Part
  isAdmin: boolean
  onAdd: () => void
  onDelete: () => void
}>) {
  const status = stockStatus(part.quantity)

  return (
    <tr className="border-b last:border-0 hover:bg-muted/30">
      <td className="p-3">
        <Link to={`/parts/${part.id}`} className="font-medium hover:underline">
          {partName(part)}
        </Link>
        {part.note && (
          <p className="max-w-72 truncate text-xs text-muted-foreground">
            {part.note}
          </p>
        )}
      </td>
      <td className="p-3">
        <div className="flex max-w-96 flex-wrap gap-1">
          {part.vehicles.map((vehicle) => (
            <Badge key={vehicle.id} variant="outline">
              {vehicleDisplayName(vehicle)}
            </Badge>
          ))}
          {part.vehicles.length === 0 && <span>Aucun véhicule</span>}
        </div>
      </td>
      <td className="p-3 text-right">
        <span className="mr-2 font-medium tabular-nums">{part.quantity}</span>
        <Badge variant={status.variant} className={status.value === "low"
          ? LOW_STOCK_BADGE_CLASS
          : undefined}>
          {status.label}
        </Badge>
      </td>
      <td className="p-3">
        {isAdmin && (
          <div className="flex justify-end gap-1">
            <Button type="button" size="sm" variant="outline" onClick={onAdd}>
              Ajouter
            </Button>
            <Button size="sm" variant="outline" asChild>
              <Link to={`/parts/${part.id}/edit`}>Modifier</Link>
            </Button>
            <Button size="icon" variant="ghost" onClick={onDelete}
              aria-label="Supprimer le stock">
              <Trash2 />
            </Button>
          </div>
        )}
      </td>
    </tr>
  )
}

