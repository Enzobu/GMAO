import { useState } from "react"
import type { ComponentProps } from "react"
import { Eye, EyeOff } from "lucide-react"

import { Input } from "@/components/ui/input"

type PasswordInputProps = Omit<ComponentProps<typeof Input>, "type">

export function PasswordInput({ className, ...props }: PasswordInputProps) {
  const [isVisible, setIsVisible] = useState(false)

  return (
    <div className="relative">
      <Input
        type={isVisible ? "text" : "password"}
        className={["pr-11", className].filter(Boolean).join(" ")}
        {...props}
      />
      <button
        type="button"
        className={[
          "absolute top-1/2 right-2 inline-flex size-8 -translate-y-1/2",
          "items-center justify-center rounded-md text-muted-foreground",
          "transition hover:bg-muted hover:text-foreground",
          "focus-visible:outline-none focus-visible:ring-2",
          "focus-visible:ring-ring",
        ].join(" ")}
        aria-label={
          isVisible ? "Masquer le mot de passe" : "Afficher le mot de passe"
        }
        onClick={() => setIsVisible((current) => !current)}
      >
        {isVisible ? (
          <EyeOff className="size-4" />
        ) : (
          <Eye className="size-4" />
        )}
      </button>
    </div>
  )
}
