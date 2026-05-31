import { Link } from "react-router-dom"
import { Save } from "lucide-react"

import { Button } from "@/components/ui/button"

type FormActionsProps = Readonly<{
  cancelTo: string
  canEdit: boolean
  isSaving: boolean
}>

export function FormActions({ cancelTo, canEdit, isSaving }: FormActionsProps) {
  return (
    <div className="flex justify-end gap-2">
      <Button variant="outline" asChild>
        <Link to={cancelTo}>Annuler</Link>
      </Button>
      <Button type="submit" disabled={!canEdit || isSaving}>
        <Save />
        {isSaving ? "Enregistrement..." : "Enregistrer"}
      </Button>
    </div>
  )
}
