"use client"

import { useState } from "react"
import { Cloud, Upload, Trash2, Pencil, Download, X, Check, FileText, Image, Video, HardDrive } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Progress } from "@/components/ui/progress"

// Données de démonstration
const demoFiles = [
  { name: "photo-vacances.jpg", type: "image" },
  { name: "video-anniversaire.mp4", type: "video" },
  { name: "document-important.pdf", type: "file" },
  { name: "capture-ecran.png", type: "image" },
]

export default function CloudPage() {
  const [files, setFiles] = useState(demoFiles)
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null)
  const [uploadProgress, setUploadProgress] = useState<number | null>(null)
  const [renamingFile, setRenamingFile] = useState<string | null>(null)
  const [newName, setNewName] = useState("")

  const freeSpace = 45.2
  const totalSpace = 100
  const usedPercent = ((totalSpace - freeSpace) / totalSpace) * 100

  const getFileIcon = (type: string) => {
    switch (type) {
      case "image":
        return <Image className="h-12 w-12 text-blue-500" />
      case "video":
        return <Video className="h-12 w-12 text-rose-500" />
      default:
        return <FileText className="h-12 w-12 text-amber-500" />
    }
  }

  const getExtension = (filename: string) => {
    return filename.split(".").pop() || ""
  }

  const getBaseName = (filename: string) => {
    const parts = filename.split(".")
    parts.pop()
    return parts.join(".")
  }

  const handleUpload = () => {
    setUploadProgress(0)
    setMessage(null)
    const interval = setInterval(() => {
      setUploadProgress((prev) => {
        if (prev === null) return 0
        if (prev >= 100) {
          clearInterval(interval)
          setMessage({ text: "Fichier uploadé avec succès !", type: "success" })
          setTimeout(() => setUploadProgress(null), 1000)
          return 100
        }
        return prev + 10
      })
    }, 100)
  }

  const handleDelete = (filename: string) => {
    if (confirm(`Supprimer ${filename} ?`)) {
      setFiles(files.filter((f) => f.name !== filename))
      setMessage({ text: `Fichier supprimé : ${filename}`, type: "success" })
    }
  }

  const handleRename = (oldName: string) => {
    if (!newName.trim()) return
    const ext = getExtension(oldName)
    const newFullName = `${newName}.${ext}`
    setFiles(files.map((f) => (f.name === oldName ? { ...f, name: newFullName } : f)))
    setMessage({ text: `Fichier renommé en : ${newFullName}`, type: "success" })
    setRenamingFile(null)
    setNewName("")
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
      {/* Header */}
      <header className="border-b border-slate-700 bg-slate-900/50 backdrop-blur-sm">
        <div className="mx-auto max-w-6xl px-4 py-6">
          <div className="flex items-center gap-3">
            <div className="rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 p-3">
              <Cloud className="h-8 w-8 text-white" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-white">Mon Cloud Familial</h1>
              <p className="text-sm text-slate-400">Stockage sécurisé pour toute la famille</p>
            </div>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-4 py-8">
        {/* Espace disque */}
        <Card className="mb-8 border-slate-700 bg-slate-800/50 backdrop-blur-sm">
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <HardDrive className="h-10 w-10 text-sky-500" />
              <div className="flex-1">
                <div className="mb-2 flex items-center justify-between">
                  <span className="font-medium text-white">Espace disque</span>
                  <span className="text-sm text-slate-400">
                    {freeSpace} Go libres sur {totalSpace} Go
                  </span>
                </div>
                <Progress value={usedPercent} className="h-3 bg-slate-700" />
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Message */}
        {message && (
          <div
            className={`mb-6 rounded-lg p-4 ${
              message.type === "success"
                ? "border border-emerald-500/30 bg-emerald-500/10 text-emerald-400"
                : "border border-red-500/30 bg-red-500/10 text-red-400"
            }`}
          >
            {message.text}
          </div>
        )}

        {/* Upload */}
        <Card className="mb-8 border-slate-700 bg-slate-800/50 backdrop-blur-sm">
          <CardContent className="p-6">
            <div className="flex flex-col items-center gap-4 sm:flex-row">
              <div className="relative flex-1">
                <Input
                  type="file"
                  className="cursor-pointer border-slate-600 bg-slate-700/50 text-white file:mr-4 file:rounded-lg file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-sky-500"
                />
              </div>
              <Button
                onClick={handleUpload}
                className="w-full bg-gradient-to-r from-sky-500 to-blue-600 text-white hover:from-sky-600 hover:to-blue-700 sm:w-auto"
              >
                <Upload className="mr-2 h-4 w-4" />
                Uploader
              </Button>
            </div>

            {/* Progress bar */}
            {uploadProgress !== null && (
              <div className="mt-4">
                <Progress value={uploadProgress} className="h-2 bg-slate-700" />
                <p className="mt-2 text-center text-sm text-slate-400">
                  {uploadProgress === 100 ? "Terminé !" : `${uploadProgress}%`}
                </p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Liste des fichiers */}
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {files.map((file) => (
            <Card
              key={file.name}
              className="group border-slate-700 bg-slate-800/50 backdrop-blur-sm transition-all hover:border-sky-500/50 hover:bg-slate-800"
            >
              <CardContent className="p-4">
                {/* Preview */}
                <div className="mb-4 flex h-32 items-center justify-center rounded-lg bg-slate-900/50">
                  {getFileIcon(file.type)}
                </div>

                {/* Nom du fichier */}
                {renamingFile === file.name ? (
                  <div className="mb-3">
                    <div className="flex items-center gap-1">
                      <Input
                        value={newName}
                        onChange={(e) => setNewName(e.target.value)}
                        className="h-8 border-slate-600 bg-slate-700 text-sm text-white"
                        autoFocus
                      />
                      <span className="text-sm text-slate-400">.{getExtension(file.name)}</span>
                    </div>
                    <div className="mt-2 flex gap-2">
                      <Button
                        size="sm"
                        onClick={() => handleRename(file.name)}
                        className="flex-1 bg-emerald-600 hover:bg-emerald-500"
                      >
                        <Check className="mr-1 h-3 w-3" />
                        Valider
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => {
                          setRenamingFile(null)
                          setNewName("")
                        }}
                        className="border-slate-600 text-slate-300 hover:bg-slate-700 hover:text-white"
                      >
                        <X className="h-3 w-3" />
                      </Button>
                    </div>
                  </div>
                ) : (
                  <p className="mb-3 truncate text-center font-medium text-white" title={file.name}>
                    <span>{getBaseName(file.name)}</span>
                    <span className="text-slate-400">.{getExtension(file.name)}</span>
                  </p>
                )}

                {/* Actions */}
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    className="flex-1 border-slate-600 text-slate-300 hover:border-red-500 hover:bg-red-500/10 hover:text-red-400"
                    onClick={() => handleDelete(file.name)}
                  >
                    <Trash2 className="mr-1 h-3 w-3" />
                    Supprimer
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    className="border-slate-600 text-slate-300 hover:border-amber-500 hover:bg-amber-500/10 hover:text-amber-400"
                    onClick={() => {
                      setRenamingFile(file.name)
                      setNewName(getBaseName(file.name))
                    }}
                  >
                    <Pencil className="h-3 w-3" />
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    className="border-slate-600 text-slate-300 hover:border-sky-500 hover:bg-sky-500/10 hover:text-sky-400"
                  >
                    <Download className="h-3 w-3" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {files.length === 0 && (
          <div className="py-16 text-center">
            <Cloud className="mx-auto mb-4 h-16 w-16 text-slate-600" />
            <h3 className="text-lg font-medium text-slate-400">Aucun fichier</h3>
            <p className="text-sm text-slate-500">Uploadez votre premier fichier pour commencer</p>
          </div>
        )}
      </main>
    </div>
  )
}
