import { exec } from 'node:child_process';
import { promisify } from 'node:util';
import { existsSync } from 'node:fs';

const executerCommande = promisify(exec);

export class ExecuteurYtdlp {
  private static cheminYtdlp = './bin/yt-dlp';

  public static async extraireDonneesJson(urlVideo: string): Promise<Record<string, unknown> | null> {
    const commandeExecutable = existsSync(this.cheminYtdlp) ? this.cheminYtdlp : 'yt-dlp';
    const commandeComplet = `${commandeExecutable} --dump-json --no-playlist --no-warnings --no-check-certificate "${urlVideo.replace(/"/g, '\\"')}"`;

    try {
      const { stdout } = await executerCommande(commandeComplet, { timeout: 30000 });
      if (!stdout || !stdout.trim()) {
        return null;
      }

      const lignes = stdout.trim().split('\n');
      for (const ligne of lignes) {
        const ligneNettoyee = ligne.trim();
        if (ligneNettoyee.startsWith('{')) {
          try {
            const objetJson = JSON.parse(ligneNettoyee);
            if (objetJson && objetJson.id) {
              return objetJson;
            }
          } catch {
            continue;
          }
        }
      }
      return null;
    } catch (erreurExecution) {
      console.error("Erreur lors de l'exécution de yt-dlp:", erreurExecution);
      return null;
    }
  }
}
