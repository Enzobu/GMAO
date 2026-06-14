import {
  createParentDocument,
  type DocumentParent,
} from "@/api/documents"
import { capitalizeFirstLetter } from "@/lib/text-format"

export type DocumentFileInput = Readonly<{
  id: string
  file: File | null
  name: string
}>

type SelectedDocumentFile = Readonly<{
  file: File
  name: string
}>

export const MAX_DOCUMENT_SIZE = 8 * 1024 * 1024
export const FILE_TOO_LARGE_MESSAGE = "Fichier trop volumineux. Max 8 Mo."
export const PDF_COMPRESSOR_URL = "https://www.ilovepdf.com/fr/compresser_pdf"

let documentFileInputCounter = 0

export function createDocumentFileInput(): DocumentFileInput {
  documentFileInputCounter += 1

  return {
    id: `document-${documentFileInputCounter}`,
    file: null,
    name: "",
  }
}

export function updateDocumentFileInput(
  inputs: DocumentFileInput[],
  id: string,
  file: File | null,
) {
  const next = inputs.map((item) => (
    item.id === id
      ? { ...item, file, name: file ? documentDisplayName(file.name) : "" }
      : item
  ))
  const isLastInput = next.at(-1)?.id === id

  return file && isLastInput ? [...next, createDocumentFileInput()] : next
}

export function updateDocumentNameInput(
  inputs: DocumentFileInput[],
  id: string,
  name: string,
) {
  return inputs.map((item) => (
    item.id === id ? { ...item, name } : item
  ))
}

export function removeDocumentFileInput(
  inputs: DocumentFileInput[],
  id: string,
) {
  const next = inputs.filter((item) => item.id !== id)

  return next.length > 0 && next.at(-1)?.file === null
    ? next
    : [...next, createDocumentFileInput()]
}

export function selectedDocumentFiles(inputs: DocumentFileInput[]) {
  return inputs
    .filter((item): item is DocumentFileInput & { file: File } => (
      item.file !== null
    ))
    .map((item): SelectedDocumentFile => ({
      file: item.file,
      name: item.name.trim() || documentDisplayName(item.file.name),
    }))
}

export function hasTooLargeDocument(inputs: DocumentFileInput[]) {
  return selectedDocumentFiles(inputs).some(
    (document) => document.file.size > MAX_DOCUMENT_SIZE,
  )
}

export async function uploadParentDocuments(
  parent: DocumentParent,
  documents: SelectedDocumentFile[],
) {
  await Promise.all(
    documents.map((document) => createParentDocument(parent, {
      file: document.file,
      name: document.name,
    })),
  )
}

export function documentDisplayName(filename: string) {
  return capitalizeFirstLetter(filename.replaceAll("_", " ").trim())
    || "Document"
}
