import { useEffect, useState } from "react"
import { isAxiosError } from "axios"
import { Link, useParams } from "react-router-dom"
import { Pencil, Play, Plus, CheckCircle2 } from "lucide-react"

import { emptyCollectionPage } from "@/api/api-collection"
import { getInterventionsPage, updateIntervention } from "@/api/interventions"
import { getVehicle } from "@/api/vehicles"
import { ListPagePlaceholder } from "@/components/loading-placeholders"
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
import { NativeSelect } from "@/components/ui/native-select"
import {
  itemsPerPageSize,
  type ItemsPerPageValue,
} from "@/components/list-page-pagination"
import { ListPaginationControls } from "@/components/list-pagination-controls"
import { useLocalStorageState } from "@/hooks/use-local-storage-state"
import { useAuthStore } from "@/stores/auth-store"
import type { Intervention } from "@/types/intervention"
import type { Vehicle } from "@/types/vehicle"
import { MIN_INPUT_DATETIME, MAX_INPUT_DATETIME } from "@/lib/date-limits"
import {
  INTERVENTION_STATUSES,
  nowInputValue,
  vehicleDisplayName,
} from "@/lib/intervention-utils"
import {
  EmptyCard,
  ErrorMessage,
  InterventionCard,
  InterventionHeader,
  MileageWarningDialog,
} from "./components"

export default function InterventionsPage({
  vehicleScoped = false,
}: Readonly<{ vehicleScoped?: boolean }>) {
  const { vehicleId } = useParams()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [interventions, setInterventions] = useState<Intervention[]>([])
  const [interventionsPage, setInterventionsPage] = useState(
    emptyCollectionPage<Intervention>(12),
  )
  const [query, setQuery] = useState("")
  const [status, setStatus] = useState("")
  const [itemsPerPage, setItemsPerPage] =
    useLocalStorageState<ItemsPerPageValue>(
      "interventions.itemsPerPage",
      "12",
    )
  const [page, setPage] = useState(1)
  const [isLoading, setIsLoading] = useState(true)
  const [isUpdatingStatus, setIsUpdatingStatus] = useState(false)
  const [quickAction, setQuickAction] = useState<QuickAction | null>(null)
  const [quickActionDate, setQuickActionDate] = useState(nowInputValue())
  const [quickActionMileage, setQuickActionMileage] = useState("")
  const [mileageDialogMessage, setMileageDialogMessage] = useState("")
  const [mileageDialogOpen, setMileageDialogOpen] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let ignore = false

    async function load() {
      try {
        const { interventionData, vehicleData } = await loadPageData(
          vehicleScoped,
          vehicleId,
          {
            page,
            itemsPerPage: itemsPerPageSize(itemsPerPage),
            search: query,
            status,
          },
        )

        if (ignore) return

        setVehicle(vehicleData)
        setInterventions(interventionData.items)
        setInterventionsPage(interventionData)
      } catch {
        if (!ignore) setError("Impossible de charger les interventions.")
      } finally {
        if (!ignore) setIsLoading(false)
      }
    }

    load()
    return () => {
      ignore = true
    }
  }, [itemsPerPage, page, query, status, vehicleId, vehicleScoped])

  const canEditVehicle = isAdmin || vehicle?.user.id === user?.id

  function openQuickAction(action: QuickAction) {
    setQuickAction(action)
    setQuickActionDate(nowInputValue())
    setQuickActionMileage(
      action.type === "finish"
        ? String(
          action.intervention.vehicle.lastMileage
            ?? action.intervention.mileage
            ?? "",
        )
        : "",
    )
  }

  async function confirmQuickAction(forceMileage = false) {
    if (!quickAction) return

    setIsUpdatingStatus(true)
    setError(null)

    try {
      const patch = quickAction.type === "start"
        ? {
          status: "in_progress" as const,
          startedAt: toApiDateTime(quickActionDate),
        }
        : {
          status: "completed" as const,
          finishedAt: toApiDateTime(quickActionDate),
          mileage: Number(quickActionMileage),
        }

      await updateIntervention(quickAction.intervention.id, patch, forceMileage)
      const { interventionData, vehicleData } = await loadPageData(
        vehicleScoped,
        vehicleId,
        {
          page,
          itemsPerPage: itemsPerPageSize(itemsPerPage),
          search: query,
          status,
        },
      )

      setVehicle(vehicleData)
      setInterventions(interventionData.items)
      setInterventionsPage(interventionData)
      setQuickAction(null)
      setMileageDialogOpen(false)
    } catch (error_) {
      if (isAxiosError(error_) && error_.response?.status === 409) {
        setMileageDialogMessage(errorMessage(error_))
        setMileageDialogOpen(true)
      } else {
        setError(errorMessage(error_))
      }
    } finally {
      setIsUpdatingStatus(false)
    }
  }

  if (isLoading) {
    return <ListPagePlaceholder filters={2} items={5} />
  }

  if (error) {
    return <ErrorMessage>{error}</ErrorMessage>
  }

  return (
    <div className="space-y-6">
      <QuickActionDialog
        action={quickAction}
        date={quickActionDate}
        mileage={quickActionMileage}
        isLoading={isUpdatingStatus}
        onDateChange={setQuickActionDate}
        onMileageChange={setQuickActionMileage}
        onOpenChange={(open) => (
          !open && !isUpdatingStatus && setQuickAction(null)
        )}
        onConfirm={() => confirmQuickAction(false)}
      />
      <MileageWarningDialog
        open={mileageDialogOpen}
        message={mileageDialogMessage}
        isAdmin={isAdmin}
        isLoading={isUpdatingStatus}
        onOpenChange={setMileageDialogOpen}
        onForce={() => confirmQuickAction(true)}
        forceLabel="Forcer"
      />

      <InterventionHeader
        title={vehicleScoped ? "Interventions du véhicule" : "Interventions"}
        description={vehicleScoped && vehicle
          ? vehicleDisplayName(vehicle)
          : "Liste globale en lecture seule."}
        backTo={vehicleScoped && vehicle
          ? `/vehicles/${vehicle.id}`
          : undefined}
        backLabel="Retour au véhicule"
        actions={vehicleScoped && canEditVehicle && vehicle
          ? (
            <Button asChild>
              <Link to={`/vehicles/${vehicle.id}/interventions/new`}>
                <Plus />
                Ajouter
              </Link>
            </Button>
          )
          : undefined}
      />

      <div
        className={
          "grid gap-3 rounded-xl border bg-card p-4 "
            + "md:grid-cols-[1fr_14rem]"
        }
      >
        <input
          value={query}
          onChange={(event) => {
            setQuery(event.target.value)
            setPage(1)
          }}
          className="h-10 rounded-lg border bg-background px-3 text-sm"
          placeholder="Rechercher une intervention..."
        />
        <NativeSelect
          label="Statut"
          value={status}
          options={INTERVENTION_STATUSES}
          onChange={(event) => {
            setStatus(event.target.value)
            setPage(1)
          }}
          placeholder="Tous"
        />
      </div>

      <ListPaginationControls
        itemLabel="intervention(s)"
        pagination={interventionsPage}
        itemsPerPage={itemsPerPage}
        onItemsPerPageChange={(value) => {
          setItemsPerPage(value)
          setPage(1)
        }}
        onPageChange={setPage}
      />

      {interventions.length === 0 ? (
        <EmptyCard>Aucune intervention trouvée.</EmptyCard>
      ) : (
        <div className="space-y-3">
          {interventions.map((intervention) => {
            const detailPath = vehicleScoped
              ? `/vehicles/${vehicleId}/interventions/${intervention.id}`
              : `/interventions/${intervention.id}`
            return (
              <InterventionCard
                key={intervention.id}
                intervention={intervention}
                to={detailPath}
                readOnly={!isAdmin && (!vehicleScoped || !canEditVehicle)}
                actions={vehicleScoped && canEditVehicle
                  ? (
                    <InterventionActions
                      intervention={intervention}
                      vehicleId={vehicleId}
                      onQuickAction={openQuickAction}
                    />
                  )
                  : undefined}
              />
            )
          })}
        </div>
      )}

      <ListPaginationControls
        itemLabel="intervention(s)"
        pagination={interventionsPage}
        itemsPerPage={itemsPerPage}
        onItemsPerPageChange={(value) => {
          setItemsPerPage(value)
          setPage(1)
        }}
        onPageChange={setPage}
      />
    </div>
  )
}

type QuickAction = { type: "start" | "finish"; intervention: Intervention }

function InterventionActions({
  intervention,
  vehicleId,
  onQuickAction,
}: Readonly<{
  intervention: Intervention
  vehicleId?: string
  onQuickAction: (action: QuickAction) => void
}>) {
  return (
    <>
      {intervention.status === "todo" && (
        <Button
          variant="outline"
          size="sm"
          onClick={() => onQuickAction({ type: "start", intervention })}
        >
          <Play />
          Démarrer
        </Button>
      )}
      {intervention.status === "in_progress" && (
        <Button
          variant="outline"
          size="sm"
          onClick={() => onQuickAction({ type: "finish", intervention })}
        >
          <CheckCircle2 />
          Terminer
        </Button>
      )}
      <Button variant="outline" size="sm" asChild>
        <Link
          to={`/vehicles/${vehicleId}/interventions/${intervention.id}/edit`}
        >
          <Pencil />
          Modifier
        </Link>
      </Button>
    </>
  )
}

function QuickActionDialog({
  action,
  date,
  mileage,
  isLoading,
  onDateChange,
  onMileageChange,
  onOpenChange,
  onConfirm,
}: Readonly<{
  action: QuickAction | null
  date: string
  mileage: string
  isLoading: boolean
  onDateChange: (value: string) => void
  onMileageChange: (value: string) => void
  onOpenChange: (open: boolean) => void
  onConfirm: () => void
}>) {
  const isStart = action?.type === "start"

  return (
    <Dialog open={action !== null} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {isStart
              ? "Démarrer l’intervention"
              : "Terminer l’intervention"}
          </DialogTitle>
          <DialogDescription>
            {isStart
              ? "Renseignez la date de début de l’intervention."
              : "Renseignez la date de fin de l’intervention."}
          </DialogDescription>
        </DialogHeader>
        <label className="grid gap-1.5 text-sm font-medium">
          <span>{isStart ? "Date de début" : "Date de fin"}</span>
          <Input
            type="datetime-local"
            min={MIN_INPUT_DATETIME}
            max={MAX_INPUT_DATETIME}
            value={date}
            onChange={(event) => onDateChange(event.target.value)}
            disabled={isLoading}
          />
        </label>
        {!isStart && (
          <label className="grid gap-1.5 text-sm font-medium">
            <span>Kilométrage</span>
            <Input
              type="number"
              min="0"
              value={mileage}
              onChange={(event) => onMileageChange(event.target.value)}
              disabled={isLoading}
            />
          </label>
        )}
        <DialogFooter>
          <Button
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={isLoading}
          >
            Annuler
          </Button>
          <Button
            onClick={onConfirm}
            disabled={isLoading || !date || (!isStart && !mileage)}
          >
            {isLoading ? "Mise à jour..." : "Confirmer"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

function toApiDateTime(value: string) {
  return value ? new Date(value).toISOString() : null
}

async function loadPageData(
  vehicleScoped: boolean,
  vehicleId: string | undefined,
  params: Readonly<{
    page: number
    itemsPerPage: number
    search: string
    status: string
  }>,
) {
  const [interventionData, vehicleData] = await Promise.all([
    getInterventionsPage({
      ...params,
      vehicleId: vehicleScoped ? vehicleId : undefined,
    }),
    vehicleScoped && vehicleId ? getVehicle(vehicleId) : Promise.resolve(null),
  ])

  return { interventionData, vehicleData }
}

function errorMessage(error: unknown) {
  if (isAxiosError(error)) {
    const data = error.response?.data as
      | { detail?: string; description?: string; message?: string }
      | undefined

    return data?.detail
      ?? data?.description
      ?? data?.message
      ?? "Impossible de mettre à jour l’intervention."
  }

  return "Impossible de mettre à jour l’intervention."
}
