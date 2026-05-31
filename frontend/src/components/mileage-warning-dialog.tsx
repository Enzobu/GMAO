import { Button } from "@/components/ui/button"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"

type MileageWarningDialogProps = Readonly<{
  open: boolean
  message: string
  isAdmin: boolean
  isLoading: boolean
  onOpenChange: (open: boolean) => void
  onForce: () => void
  forceLabel?: string
}>

export function MileageWarningDialog({
  open,
  message,
  isAdmin,
  isLoading,
  onOpenChange,
  onForce,
  forceLabel = "Forcer",
}: MileageWarningDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Kilométrage à vérifier</DialogTitle>
          <DialogDescription>{message}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={isLoading}>Fermer</Button>
          {isAdmin && <Button onClick={onForce} disabled={isLoading}>{isLoading ? "Enregistrement..." : forceLabel}</Button>}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
