import type { ReactNode } from "react"
import { Link } from "react-router-dom"
import { ArrowLeft, Pencil, Trash2 } from "lucide-react"

import { Button } from "@/components/ui/button"

export function DetailPageActions({
  backTo,
  editTo,
  editLabel = "Modifier",
  extraActions,
  onDelete,
}: Readonly<{
  backTo: string
  editTo?: string
  editLabel?: string
  extraActions?: ReactNode
  onDelete?: () => void
}>) {
  return (
    <div className="flex flex-col gap-2 sm:flex-row">
      <Button variant="outline" asChild>
        <Link to={backTo}>
          <ArrowLeft />
          Retour
        </Link>
      </Button>
      {extraActions}
      {editTo && (
        <Button asChild>
          <Link to={editTo}>
            <Pencil />
            {editLabel}
          </Link>
        </Button>
      )}
      {onDelete && (
        <Button variant="destructive" onClick={onDelete}>
          <Trash2 />
          Supprimer
        </Button>
      )}
    </div>
  )
}
