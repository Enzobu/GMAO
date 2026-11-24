import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"

export interface StockMovement {
  id: string
  name: string
  delta: number
}

interface Props {
  open: boolean
  isLoading: boolean
  movements: StockMovement[]
  onOpenChange: (open: boolean) => void
  onConfirm: () => void
}

export function StockMovementDialog({
  open,
  isLoading,
  movements,
  onOpenChange,
  onConfirm,
}: Readonly<Props>) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Confirmer les mouvements de stock</DialogTitle>
          <DialogDescription>
            Ces quantités seront ajustées lors de l’enregistrement.
          </DialogDescription>
        </DialogHeader>
        <ul className="space-y-1 text-sm">
          {movements.map((movement) => (
            <li key={movement.id}>
              {movement.name} : {movement.delta > 0 ? "-" : "+"}
              {Math.abs(movement.delta)}
            </li>
          ))}
        </ul>
        <DialogFooter>
          <Button
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={isLoading}
          >
            Annuler
          </Button>
          <Button onClick={onConfirm} disabled={isLoading}>
            {isLoading ? "Enregistrement..." : "Confirmer"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
