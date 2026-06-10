import type { Dispatch, SetStateAction } from "react"

import type { DocumentFileInput } from "@/lib/form-documents"
import {
  removeDocumentFileInput,
  updateDocumentFileInput,
  updateDocumentNameInput,
} from "@/lib/form-documents"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"

const REMOVE_DOCUMENT_BUTTON_CLASS = [
  "rounded-lg border px-3 py-2 text-sm hover:bg-muted",
  "disabled:pointer-events-none disabled:opacity-50",
].join(" ")

export function FormDocumentsField({
  canEdit,
  documentFiles,
  isSaving,
  setDocumentFiles,
}: Readonly<{
  canEdit: boolean
  documentFiles: DocumentFileInput[]
  isSaving: boolean
  setDocumentFiles: Dispatch<SetStateAction<DocumentFileInput[]>>
}>) {
  function updateDocumentFile(id: string, file: File | null) {
    setDocumentFiles((current) => updateDocumentFileInput(current, id, file))
  }

  function removeDocumentFile(id: string) {
    setDocumentFiles((current) => removeDocumentFileInput(current, id))
  }

  function updateDocumentName(id: string, name: string) {
    setDocumentFiles((current) => updateDocumentNameInput(current, id, name))
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Documents</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        <div className="space-y-3">
          {documentFiles.map(({ file, id, name }, index) => (
            <label key={id} className="grid gap-1.5 text-sm font-medium">
              <span>Document {index + 1}</span>
              <div className="grid gap-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                <Input
                  type="file"
                  disabled={!canEdit || isSaving}
                  onChange={(event) => {
                    updateDocumentFile(id, event.target.files?.[0] ?? null)
                  }}
                />

                <Input
                  type="text"
                  value={name}
                  disabled={!file || !canEdit || isSaving}
                  placeholder="Nom affiché du document"
                  onChange={(event) => {
                    updateDocumentName(id, event.target.value)
                  }}
                />

                {file && (
                  <button
                    type="button"
                    className={REMOVE_DOCUMENT_BUTTON_CLASS}
                    disabled={!canEdit || isSaving}
                    onClick={() => removeDocumentFile(id)}
                  >
                    Retirer
                  </button>
                )}
              </div>

              {file && (
                <span className="truncate text-xs text-muted-foreground">
                  Fichier sélectionné : {file.name}
                </span>
              )}
            </label>
          ))}
        </div>
        <p className="text-xs text-muted-foreground">
          Les documents sélectionnés seront liés à la ressource après
          l’enregistrement.
        </p>
      </CardContent>
    </Card>
  )
}
