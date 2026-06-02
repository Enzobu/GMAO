export type ApiCollection<T> =
  | T[]
  | {
      member?: T[]
      "hydra:member"?: T[]
    }
  | null
  | undefined

export function collectionItems<T>(data: ApiCollection<T>) {
  if (Array.isArray(data)) {
    return data
  }

  return data?.member ?? data?.["hydra:member"] ?? []
}
