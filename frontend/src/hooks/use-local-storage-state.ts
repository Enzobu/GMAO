import { useEffect, useState } from "react"

export function useLocalStorageState<T extends string>(
  key: string,
  defaultValue: T,
) {
  const [value, setValue] = useState<T>(() => {
    const storedValue = localStorage.getItem(key)

    return (storedValue as T | null) ?? defaultValue
  })

  useEffect(() => {
    localStorage.setItem(key, value)
  }, [key, value])

  return [value, setValue] as const
}
