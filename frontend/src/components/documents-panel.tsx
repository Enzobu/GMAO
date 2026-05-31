import { useEffect, useState } from "react"
import type { FormEvent, ReactNode } from "react"
import { AxiosError } from "axios"
import { Download, Eye, FileText, Pencil, Plus, Trash2, Upload } from "lucide-react"

import {
  createProfileDocument,
  createParentDocument,
  deleteProfileDocument,
  deleteParentDocument,
  getParentDocumentBlob,
  getParentDocuments,
  getProfileDocumentBlob,
  getProfileDocuments,
  type DocumentParent,
  updateParentDocument,
  updateProfileDocument,
} from "@/api/documents"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { ConfirmDialog } from "@/components/ui/confirm-dialog"
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
import type { AppDocument } from "@/types/document"

type DocumentFormState = {
  name: string
  description: string
  file: File | null
}

const emptyForm: DocumentFormState = {
  name: "",
  description: "",
  file: null,
}

const MAX_DOCUMENT_SIZE = 8 * 1024 * 1024

export function DocumentsPanel({
  canDelete,
  canManage = true,
  parent,
  emptyLabel = "Aucun document disponible.",
}: Readonly<{
  canDelete: boolean
  canManage?: boolean
  parent?: DocumentParent
  emptyLabel?: string
}>) {
  const [documents, setDocuments] = useState<AppDocument[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState("")
  const [form, setForm] = useState<DocumentFormState>(emptyForm)
  const [formError, setFormError] = useState("")
  const [editingDocument, setEditingDocument] = useState<AppDocument | null>(null)
  const [isFormOpen, setIsFormOpen] = useState(false)
  const [isSaving, setIsSaving] = useState(false)
  const [documentToDelete, setDocumentToDelete] = useState<AppDocument | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)
  const [previewDocument, setPreviewDocument] = useState<AppDocument | null>(null)
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)
  const [isPreviewLoading, setIsPreviewLoading] = useState(false)

  useEffect(() => {
    let ignore = false

    async function loadDocuments() {
      try {
        setError("")
        const data = parent === undefined ? await getProfileDocuments() : await getParentDocuments(parent)

        if (!ignore) {
          setDocuments(data)
        }
      } catch {
        if (!ignore) {
          setError("Impossible de charger les documents.")
        }
      } finally {
        if (!ignore) {
          setIsLoading(false)
        }
      }
    }

    void loadDocuments()

    return () => {
      ignore = true
    }
  }, [parent?.type, parent?.id])

  useEffect(() => () => revokePreviewUrl(previewUrl), [previewUrl])

  function openCreateForm() {
    setEditingDocument(null)
    setForm(emptyForm)
    setFormError("")
    setIsFormOpen(true)
  }

  function openEditForm(document: AppDocument) {
    setEditingDocument(document)
    setForm({
      name: document.name,
      description: document.description ?? "",
      file: null,
    })
    setFormError("")
    setIsFormOpen(true)
  }

  async function submitForm(event: FormEvent) {
    event.preventDefault()
    setError("")
    setFormError("")

    const name = form.name.trim()
    if (!name) {
      setFormError("Le nom du document est obligatoire.")
      return
    }

    if (!editingDocument && form.file === null) {
      setFormError("Le fichier est obligatoire.")
      return
    }

    if (!editingDocument && form.file && form.file.size > MAX_DOCUMENT_SIZE) {
      setFormError("Fichier trop volumineux. Max 8 Mo.")
      return
    }

    setIsSaving(true)

    try {
      const description = form.description.trim() || null
      const savedDocument = editingDocument
        ? await updateDocument(parent, editingDocument.publicId, { name, description })
        : await createDocument(parent, { name, description, file: form.file as File })

      setDocuments((current) => {
        if (editingDocument) {
          return current.map((document) => document.publicId === savedDocument.publicId ? savedDocument : document)
        }

        return [savedDocument, ...current]
      })
      setIsFormOpen(false)
      setForm(emptyForm)
      setEditingDocument(null)
    } catch (caught) {
      setFormError(errorMessage(caught, editingDocument ? "Impossible de modifier ce document." : "Impossible d’ajouter ce document."))
    } finally {
      setIsSaving(false)
    }
  }

  async function confirmDelete() {
    if (!documentToDelete) {
      return
    }

    setIsDeleting(true)
    setError("")

    try {
      await deleteDocument(parent, documentToDelete.publicId)
      setDocuments((current) => current.filter((document) => document.publicId !== documentToDelete.publicId))
      setDocumentToDelete(null)
    } catch {
      setError("Impossible d’archiver ce document.")
    } finally {
      setIsDeleting(false)
    }
  }

  async function openPreview(document: AppDocument) {
    setPreviewDocument(document)
    setIsPreviewLoading(true)
    revokePreviewUrl(previewUrl)
    setPreviewUrl(null)

    try {
      const blob = await getDocumentBlob(parent, document.publicId)
      setPreviewUrl(URL.createObjectURL(blob))
    } catch {
      setError("Impossible d’ouvrir ce document.")
      setPreviewDocument(null)
    } finally {
      setIsPreviewLoading(false)
    }
  }

  async function downloadDocument(document: AppDocument) {
    try {
      const blob = await getDocumentBlob(parent, document.publicId, true)
      const url = URL.createObjectURL(blob)
      const link = globalThis.document.createElement("a")
      link.href = url
      link.download = document.originalFilename ?? document.name
      link.click()
      URL.revokeObjectURL(url)
    } catch {
      setError("Impossible de télécharger ce document.")
    }
  }

  return (
    <Card className="rounded-3xl border-border bg-card shadow-sm">
      <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <CardTitle className="flex items-center gap-3">
          <FileText className="h-5 w-5 text-primary" />
          Documents
        </CardTitle>
        {canManage && (
          <Button type="button" onClick={openCreateForm}>
            <Plus />
            Ajouter un document
          </Button>
        )}
      </CardHeader>

      <CardContent className="space-y-4">
        {error && <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">{error}</div>}

        {isLoading ? (
          <div className="text-sm text-muted-foreground">Chargement des documents...</div>
        ) : documents.length === 0 ? (
          <div className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">{emptyLabel}</div>
        ) : (
          <div className="divide-y rounded-lg border">
            {documents.map((document) => (
              <div key={document.publicId} className="flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
                <div className="min-w-0 space-y-1">
                  <div className="truncate font-medium">{document.name || "Document sans nom"}</div>
                  <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                    <span>Ajouté le {formatDate(document.createdAt)}</span>
                    <span>Modifié le {formatDate(document.updatedAt)}</span>
                    <span>{formatFileDetails(document)}</span>
                  </div>
                  <div className="text-sm text-muted-foreground">{shortDescription(document.description)}</div>
                </div>

                <div className="flex flex-wrap gap-2">
                  <Button type="button" variant="outline" onClick={() => void openPreview(document)}>
                    <Eye />
                    Voir
                  </Button>
                  <Button type="button" variant="outline" onClick={() => void downloadDocument(document)}>
                    <Download />
                    Télécharger
                  </Button>
                  {canManage && (
                    <Button type="button" variant="outline" onClick={() => openEditForm(document)}>
                      <Pencil />
                      Modifier
                    </Button>
                  )}
                  {canDelete && (
                    <Button type="button" variant="destructive" onClick={() => setDocumentToDelete(document)}>
                      <Trash2 />
                      Archiver
                    </Button>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>

      <DocumentFormDialog
        document={editingDocument}
        form={form}
        isOpen={isFormOpen}
        isSaving={isSaving}
        error={formError}
        onFormChange={setForm}
        onOpenChange={(open) => !isSaving && setIsFormOpen(open)}
        onSubmit={submitForm}
      />

      <DocumentPreviewDialog
        document={previewDocument}
        previewUrl={previewUrl}
        isLoading={isPreviewLoading}
        onOpenChange={(open) => {
          if (!open) {
            setPreviewDocument(null)
            revokePreviewUrl(previewUrl)
            setPreviewUrl(null)
          }
        }}
      />

      <ConfirmDialog
        open={documentToDelete !== null}
        title="Archiver le document ?"
        description={documentToDelete ? `${documentToDelete.name} sera masqué de la plateforme.` : ""}
        confirmLabel="Archiver"
        isLoading={isDeleting}
        onOpenChange={(open) => !isDeleting && !open && setDocumentToDelete(null)}
        onConfirm={confirmDelete}
      />
    </Card>
  )
}

function DocumentFormDialog({
  document,
  form,
  isOpen,
  isSaving,
  error,
  onFormChange,
  onOpenChange,
  onSubmit,
}: Readonly<{
  document: AppDocument | null
  form: DocumentFormState
  isOpen: boolean
  isSaving: boolean
  error: string
  onFormChange: (form: DocumentFormState) => void
  onOpenChange: (open: boolean) => void
  onSubmit: (event: FormEvent) => void
}>) {
  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent>
        <form onSubmit={onSubmit} className="space-y-4">
          <DialogHeader>
            <DialogTitle>{document ? "Modifier le document" : "Ajouter un document"}</DialogTitle>
            <DialogDescription>
              {document ? "Modifiez le nom ou la description du document." : "Ajoutez un fichier à votre profil."}
            </DialogDescription>
          </DialogHeader>

          {error && <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">{error}</div>}

          {!document && (
            <Field label="Fichier">
              <Input
                type="file"
                disabled={isSaving}
                onChange={(event) => {
                  const file = event.target.files?.[0] ?? null
                  onFormChange({
                    ...form,
                    file,
                    name: form.name || (file ? file.name.replace(/\.[^.]+$/, "") : ""),
                  })
                }}
              />
            </Field>
          )}

          <Field label="Nom">
            <Input value={form.name} disabled={isSaving} onChange={(event) => onFormChange({ ...form, name: event.target.value })} />
          </Field>

          <Field label="Description">
            <Textarea value={form.description} disabled={isSaving} onChange={(event) => onFormChange({ ...form, description: event.target.value })} />
          </Field>

          <DialogFooter>
            <Button type="button" variant="outline" disabled={isSaving} onClick={() => onOpenChange(false)}>
              Annuler
            </Button>
            <Button type="submit" disabled={isSaving}>
              <Upload />
              {isSaving ? "Enregistrement..." : "Enregistrer"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function errorMessage(caught: unknown, fallback: string) {
  if (caught instanceof AxiosError) {
    const message = caught.response?.data?.message ?? caught.response?.data?.detail
    if (typeof message === "string" && message.trim()) {
      return message
    }

    if (caught.response?.status === 413) {
      return "Fichier trop volumineux. Max 8 Mo."
    }
  }

  return fallback
}

function DocumentPreviewDialog({
  document,
  previewUrl,
  isLoading,
  onOpenChange,
}: Readonly<{
  document: AppDocument | null
  previewUrl: string | null
  isLoading: boolean
  onOpenChange: (open: boolean) => void
}>) {
  return (
    <Dialog open={document !== null} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-5xl">
        <DialogHeader>
          <DialogTitle>{document?.name ?? "Document"}</DialogTitle>
          {document?.originalFilename && <DialogDescription>{document.originalFilename}</DialogDescription>}
        </DialogHeader>

        <div className="h-[70vh] overflow-hidden rounded-lg border bg-muted/40">
          {isLoading && <div className="flex h-full items-center justify-center text-sm text-muted-foreground">Chargement du document...</div>}
          {!isLoading && document && previewUrl && <PreviewContent document={document} previewUrl={previewUrl} />}
        </div>
      </DialogContent>
    </Dialog>
  )
}

function PreviewContent({ document, previewUrl }: Readonly<{ document: AppDocument; previewUrl: string }>) {
  if (isPdf(document)) {
    return <iframe src={previewUrl} title={document.name} className="h-full w-full border-0" />
  }

  if (isImage(document)) {
    return <img src={previewUrl} alt={document.name} className="h-full w-full object-contain" />
  }

  return (
    <div className="flex h-full flex-col items-center justify-center gap-3 p-6 text-center text-sm text-muted-foreground">
      <FileText className="h-10 w-10" />
      <div>Aperçu indisponible pour ce type de fichier.</div>
      <Button variant="outline" asChild>
        <a href={previewUrl} target="_blank" rel="noreferrer">Ouvrir dans un nouvel onglet</a>
      </Button>
    </div>
  )
}

function Field({ label, children }: Readonly<{ label: string; children: ReactNode }>) {
  return (
    <label className="grid gap-1.5 text-sm font-medium">
      <span>{label}</span>
      {children}
    </label>
  )
}

function formatDate(value: string) {
  return new Intl.DateTimeFormat("fr-FR", { dateStyle: "short", timeStyle: "short" }).format(new Date(value))
}

function formatFileDetails(document: AppDocument) {
  const extension = document.extension ? document.extension.toUpperCase() : "Fichier"
  const size = document.size == null ? null : formatBytes(document.size)

  return size ? `${extension} ・ ${size}` : extension
}

function formatBytes(size: number) {
  if (size < 1024) return `${size} o`
  if (size < 1024 * 1024) return `${Math.round(size / 1024)} Ko`

  return `${(size / 1024 / 1024).toFixed(1)} Mo`
}

function shortDescription(description?: string | null) {
  if (!description) return "Aucune description à afficher"

  return description.length > 80 ? `${description.slice(0, 80)}...` : description
}

function isPdf(document: AppDocument) {
  return document.extension?.toLowerCase() === "pdf" || document.mimeType === "application/pdf"
}

function isImage(document: AppDocument) {
  return document.mimeType?.startsWith("image/") ?? false
}

function revokePreviewUrl(url: string | null) {
  if (url) {
    URL.revokeObjectURL(url)
  }
}

function createDocument(parent: DocumentParent | undefined, payload: { name: string; description: string | null; file: File }) {
  return parent === undefined ? createProfileDocument(payload) : createParentDocument(parent, payload)
}

function updateDocument(parent: DocumentParent | undefined, publicId: string, payload: { name: string; description: string | null }) {
  return parent === undefined ? updateProfileDocument(publicId, payload) : updateParentDocument(parent, publicId, payload)
}

function deleteDocument(parent: DocumentParent | undefined, publicId: string) {
  return parent === undefined ? deleteProfileDocument(publicId) : deleteParentDocument(parent, publicId)
}

function getDocumentBlob(parent: DocumentParent | undefined, publicId: string, download = false) {
  return parent === undefined ? getProfileDocumentBlob(publicId, download) : getParentDocumentBlob(parent, publicId, download)
}
