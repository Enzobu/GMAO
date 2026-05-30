import { useEffect, useMemo, useState } from "react"
import type { FormEvent } from "react"
import { AxiosError } from "axios"
import { Pencil, Plus, Search, Trash2 } from "lucide-react"

import {
  createMaintenanceType,
  createPartType,
  deleteMaintenanceType,
  deletePartType,
  getMaintenanceTypes,
  getPartTypes,
  updateMaintenanceType,
  updatePartType,
} from "@/api/configuration"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import type { ConfigurationItem, ConfigurationPayload } from "@/types/configuration"

type ResourceKind = "maintenance" | "part"

interface ResourceConfig {
  kind: ResourceKind
  title: string
  description: string
  emptyTitle: string
  emptyDescription: string
  deleteTitle: string
  deleteDescription: (item: ConfigurationItem) => string
  getItems: () => Promise<ConfigurationItem[]>
  createItem: (payload: ConfigurationPayload) => Promise<ConfigurationItem>
  updateItem: (id: number, payload: ConfigurationPayload) => Promise<ConfigurationItem>
  deleteItem: (id: number) => Promise<void>
}

const resources: ResourceConfig[] = [
  {
    kind: "maintenance",
    title: "Types d’entretiens",
    description: "Catégories utilisées dans les interventions et entretiens.",
    emptyTitle: "Aucun type d’entretien",
    emptyDescription: "Créez un premier type pour structurer les entretiens.",
    deleteTitle: "Supprimer le type d’entretien ?",
    deleteDescription: (item) => `${item.name} ne sera plus proposé dans les formulaires, mais restera visible sur les entretiens existants.`,
    getItems: getMaintenanceTypes,
    createItem: createMaintenanceType,
    updateItem: updateMaintenanceType,
    deleteItem: deleteMaintenanceType,
  },
  {
    kind: "part",
    title: "Types de pièces",
    description: "Catégories utilisées pour organiser le stock de pièces.",
    emptyTitle: "Aucun type de pièce",
    emptyDescription: "Créez un premier type pour classer les pièces du stock.",
    deleteTitle: "Supprimer le type de pièce ?",
    deleteDescription: (item) => `${item.name} sera masqué de la configuration. La suppression sera refusée si des pièces utilisent encore ce type.`,
    getItems: getPartTypes,
    createItem: createPartType,
    updateItem: updatePartType,
    deleteItem: deletePartType,
  },
]

export default function ConfigurationPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Configuration</h1>
        <p className="text-sm text-muted-foreground">Gérez les référentiels utilisés par la GMAO.</p>
      </div>

      <Accordion type="multiple" className="space-y-4">
        {resources.map((resource) => (
          <ConfigurationResourcePanel key={resource.kind} resource={resource} />
        ))}
      </Accordion>
    </div>
  )
}

function ConfigurationResourcePanel({ resource }: Readonly<{ resource: ResourceConfig }>) {
  const [items, setItems] = useState<ConfigurationItem[]>([])
  const [search, setSearch] = useState("")
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [formItem, setFormItem] = useState<ConfigurationItem | null | "new">(null)
  const [deleteItem, setDeleteItem] = useState<ConfigurationItem | null>(null)
  const [isSaving, setIsSaving] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)

  useEffect(() => {
    let ignore = false

    async function loadItems() {
      try {
        const data = await resource.getItems()

        if (!ignore) {
          setItems(data.toSorted(compareItems))
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger ces données de configuration.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    loadItems()

    return () => {
      ignore = true
    }
  }, [resource])

  const filteredItems = useMemo(() => {
    const normalizedSearch = normalize(search)

    return items
      .filter((item) => {
        if (!normalizedSearch) {
          return true
        }

        return normalize(`${item.name} ${item.description ?? ""}`).includes(normalizedSearch)
      })
      .sort(compareItems)
  }, [items, search])

  async function handleSave(payload: ConfigurationPayload) {
    if (!formItem) {
      return
    }

    setIsSaving(true)
    setError(null)

    try {
      const saved = formItem === "new"
        ? await resource.createItem(payload)
        : await resource.updateItem(formItem.id, payload)

      setItems((current) => upsertItem(current, saved).sort(compareItems))
      setFormItem(null)
    } catch {
      setError("Impossible d’enregistrer. Vérifiez les champs saisis.")
    } finally {
      setIsSaving(false)
    }
  }

  async function handleDelete() {
    if (!deleteItem) {
      return
    }

    setIsDeleting(true)
    setError(null)

    try {
      await resource.deleteItem(deleteItem.id)
      setItems((current) => current.filter((item) => item.id !== deleteItem.id))
      setDeleteItem(null)
    } catch (error_) {
      setError(errorMessage(error_, "Impossible de supprimer cet élément."))
    } finally {
      setIsDeleting(false)
    }
  }

  function renderItemsContent() {
    if (isLoading) {
      return <div className="text-sm text-muted-foreground">Chargement...</div>
    }

    if (items.length === 0) {
      return (
        <div className="rounded-lg border p-6 text-center">
          <div className="font-medium">{resource.emptyTitle}</div>
          <div className="mt-1 text-sm text-muted-foreground">{resource.emptyDescription}</div>
        </div>
      )
    }

    if (filteredItems.length === 0) {
      return <div className="rounded-lg border p-6 text-center text-sm text-muted-foreground">Aucun résultat pour cette recherche.</div>
    }

    return (
      <div className="space-y-2">
        {filteredItems.map((item) => (
          <div key={item.id} className="rounded-lg border border-foreground/10 p-3">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                  <div className="font-medium">{item.name}</div>
                  <Badge variant={item.isDeleted ? "outline" : "secondary"}>{item.isDeleted ? "Supprimé" : "Actif"}</Badge>
                </div>
                <p className="mt-1 text-sm text-muted-foreground">{item.description || "—"}</p>
              </div>

              {!item.isDeleted && (
                <div className="flex shrink-0 justify-end gap-2">
                  <Button variant="outline" size="sm" onClick={() => setFormItem(item)}>
                    <Pencil />
                    Modifier
                  </Button>
                  <Button variant="destructive" size="sm" onClick={() => setDeleteItem(item)}>
                    <Trash2 />
                    Supprimer
                  </Button>
                </div>
              )}
            </div>
          </div>
        ))}
      </div>
    )
  }

  return (
    <AccordionItem value={resource.kind}>
      <AccordionTrigger>
        <div className="flex min-w-0 flex-1 flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
          <div className="min-w-0">
            <div className="font-heading text-base font-medium">{resource.title}</div>
            <p className="text-sm font-normal text-muted-foreground">{resource.description}</p>
          </div>

          <Badge variant="outline" className="w-fit shrink-0">
            {search ? `${filteredItems.length} / ${items.length}` : items.length} élément(s)
          </Badge>
        </div>
      </AccordionTrigger>

      <AccordionContent>
        <Card className="overflow-visible rounded-none border-0 bg-transparent py-0 ring-0">
          <CardContent className="space-y-4 px-0">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
              <label className="grid flex-1 gap-1.5 text-sm font-medium" htmlFor="configuration-search">
                <span>Recherche</span>
                <div className="relative min-w-0">
                  <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                  <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Nom ou description..." className="pl-8" id="configuration-search" />
                </div>
              </label>

              <Button onClick={() => setFormItem("new")}>
                <Plus />
                Ajouter
              </Button>
            </div>

            {error && <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">{error}</div>}

            {renderItemsContent()}
          </CardContent>
        </Card>
      </AccordionContent>

      <ConfigurationFormDialog
        item={formItem}
        title={formItem === "new" ? `Ajouter - ${resource.title}` : `Modifier - ${resource.title}`}
        isSaving={isSaving}
        onOpenChange={(open) => {
          if (!open && !isSaving) {
            setFormItem(null)
          }
        }}
        onSubmit={handleSave}
      />

      <ConfirmDialog
        open={deleteItem !== null}
        title={resource.deleteTitle}
        description={deleteItem ? resource.deleteDescription(deleteItem) : ""}
        confirmLabel="Supprimer"
        isLoading={isDeleting}
        onOpenChange={(open) => {
          if (!open && !isDeleting) {
            setDeleteItem(null)
          }
        }}
        onConfirm={handleDelete}
      />
    </AccordionItem>
  )
}

function ConfigurationFormDialog({ item, title, isSaving, onOpenChange, onSubmit }: Readonly<{ item: ConfigurationItem | "new" | null; title: string; isSaving: boolean; onOpenChange: (open: boolean) => void; onSubmit: (payload: ConfigurationPayload) => Promise<void> }>) {
  const [name, setName] = useState("")
  const [description, setDescription] = useState("")

  useEffect(() => {
    if (!item) {
      return
    }

    setName(item === "new" ? "" : item.name)
    setDescription(item === "new" ? "" : item.description ?? "")
  }, [item])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    await onSubmit({
      name,
      description: description || null,
    })
  }

  return (
    <Dialog open={item !== null} onOpenChange={onOpenChange}>
      <DialogContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <DialogHeader>
            <DialogTitle>{title}</DialogTitle>
            <DialogDescription>Renseignez le nom et la description affichés dans les formulaires métier.</DialogDescription>
          </DialogHeader>

          <label className="grid gap-1.5 text-sm font-medium">
            <span>Nom</span>
            <Input value={name} onChange={(event) => setName(event.target.value)} required autoFocus />
          </label>

          <label className="grid gap-1.5 text-sm font-medium">
            <span>Description</span>
            <Textarea value={description} onChange={(event) => setDescription(event.target.value)} />
          </label>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={isSaving}>Annuler</Button>
            <Button type="submit" disabled={isSaving}>{isSaving ? "Enregistrement..." : "Enregistrer"}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function upsertItem(items: ConfigurationItem[], item: ConfigurationItem) {
  const exists = items.some((current) => current.id === item.id)

  if (!exists) {
    return [...items, item]
  }

  return items.map((current) => current.id === item.id ? item : current)
}

function compareItems(first: ConfigurationItem, second: ConfigurationItem) {
  return first.name.localeCompare(second.name, "fr")
}

function normalize(value: string) {
  return value
    .toLowerCase()
    .normalize("NFD")
    .replaceAll(/[\u0300-\u036f]/g, "")
}

function errorMessage(caught: unknown, fallback: string) {
  if (caught instanceof AxiosError) {
    const detail = caught.response?.data?.detail ?? caught.response?.data?.message

    if (typeof detail === "string") {
      return detail
    }
  }

  return fallback
}
