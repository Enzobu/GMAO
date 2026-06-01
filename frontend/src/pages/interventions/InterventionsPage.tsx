import { useEffect, useMemo, useState } from "react"
import { isAxiosError } from "axios"
import { Link, useParams } from "react-router-dom"
import { Pencil, Play, Plus, CheckCircle2 } from "lucide-react"

import { getInterventions, updateIntervention } from "@/api/interventions"
import { getVehicle } from "@/api/vehicles"
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
import { PaginationControls } from "@/components/ui/pagination-controls"
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

type ItemsPerPageValue = "6" | "12" | "24" | "all"

export default function InterventionsPage({
  vehicleScoped = false,
}: Readonly<{ vehicleScoped?: boolean }>) {
  const { vehicleId } = useParams()
  const user = useAuthStore((state) => state.user)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const [vehicle, setVehicle] = useState<Vehicle | null>(null)
  const [interventions, setInterventions] = useState<Intervention[]>([])
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
        )

        if (ignore) return

        setVehicle(vehicleData)
        setInterventions(
          vehicleId
            ? interventionData.filter(
              (item) => item.vehicle.id === Number(vehicleId),
            )
            : interventionData,
        )
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
  }, [vehicleId, vehicleScoped])

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
      )

      setVehicle(vehicleData)
      setInterventions(
        vehicleId
          ? interventionData.filter(
            (item) => item.vehicle.id === Number(vehicleId),
          )
          : interventionData,
      )
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

  const filtered = useMemo(() => {
    return interventions
      .filter((item) => !status || item.status === status)
      .filter((item) => {
        const text = [
          item.maintenanceType?.name ?? "",
          item.vehicle?.registration ?? "",
          item.notes ?? "",
        ].join(" ").toLowerCase()
        return text.includes(query.toLowerCase().trim())
      })
      .sort((a, b) => String(
        b.finishedAt ?? b.startedAt ?? b.plannedAt ?? b.createdAt ?? "",
      ).localeCompare(String(
        a.finishedAt ?? a.startedAt ?? a.plannedAt ?? a.createdAt ?? "",
      )))
  }, [interventions, query, status])
  const pageSize = itemsPerPage === "all"
    ? filtered.length || 1
    : Number(itemsPerPage)
  const totalPages = itemsPerPage === "all"
    ? 1
    : Math.max(1, Math.ceil(filtered.length / pageSize))
  const currentPage = Math.min(page, totalPages)
  const visible = itemsPerPage === "all"
    ? filtered
    : filtered.slice((currentPage - 1) * pageSize, currentPage * pageSize)
  const visibleStart = filtered.length === 0
    ? 0
    : (currentPage - 1) * pageSize + 1
  const visibleEnd = itemsPerPage === "all"
    ? filtered.length
    : Math.min(currentPage * pageSize, filtered.length)

  if (isLoading) {
    return (
      <div className="text-sm text-muted-foreground">
        Chargement des interventions...
      </div>
    )
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

      {visible.length === 0 ? (
        <EmptyCard>Aucune intervention trouvée.</EmptyCard>
      ) : (
        <div className="space-y-3">
          {visible.map((intervention) => {
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

      <PaginationControls
        currentPage={currentPage}
        pageCount={totalPages}
        totalItems={filtered.length}
        visibleStart={visibleStart}
        visibleEnd={visibleEnd}
        itemsPerPage={itemsPerPage}
        itemsPerPageOptions={[
          { value: "6", label: "6" },
          { value: "12", label: "12" },
          { value: "24", label: "24" },
          { value: "all", label: "Tout" },
        ]}
        onItemsPerPageChange={(value) => {
          setItemsPerPage(value as ItemsPerPageValue)
          setPage(1)
        }}
        onPreviousPage={() => setPage((current) => Math.max(1, current - 1))}
        onNextPage={() => setPage(
          (current) => Math.min(totalPages, current + 1),
        )}
        itemLabel="intervention(s)"
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

async function loadPageData(vehicleScoped: boolean, vehicleId?: string) {
  const [interventionData, vehicleData] = await Promise.all([
    getInterventions(),
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
