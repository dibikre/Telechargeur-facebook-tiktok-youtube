import { InterfaceRecuperateurBackend, ResultatExtractionBackend, FormatStructure } from '../interfaces/interface_recuperateur';
import { ExecuteurYtdlp } from '../utilitaires/executeur_ytdlp';

export class RecuperateurYouTubeBackend implements InterfaceRecuperateurBackend {
  public correspondA(urlVideo: string): boolean {
    return /youtube\.com|youtu\.be/i.test(urlVideo);
  }

  public async extraire(urlVideo: string): Promise<ResultatExtractionBackend | null> {
    const donneesJson = await ExecuteurYtdlp.extraireDonneesJson(urlVideo);
    if (!donneesJson) {
      return null;
    }

    const identifiant = String(donneesJson['id'] || 'yt_' + Date.now());
    const titre = String(donneesJson['title'] || 'Vidéo YouTube');
    const auteur = String(donneesJson['uploader'] || donneesJson['channel'] || 'Auteur inconnu');
    const dureeSecondes = Number(donneesJson['duration'] || 0);
    const dureeTexte = this.formaterDuree(dureeSecondes);
    const miniatureUrl = String(donneesJson['thumbnail'] || `https://i.ytimg.com/vi/${identifiant}/hqdefault.jpg`);
    const nombreVues = donneesJson['view_count'] ? `${donneesJson['view_count']} vues` : undefined;

    const formatsBruts = Array.isArray(donneesJson['formats']) ? donneesJson['formats'] : [];
    const formatsConstructes: FormatStructure[] = [];

    for (const f of formatsBruts) {
      const urlTelecharge = f['url'];
      if (!urlTelecharge) continue;

      const vcodec = String(f['vcodec'] || 'none');
      const acodec = String(f['acodec'] || 'none');
      const ext = String(f['ext'] || 'mp4');
      const hauteur = Number(f['height'] || 0);
      const estAudioPur = vcodec === 'none' && acodec !== 'none';
      const estVideo = vcodec !== 'none';

      if (estVideo) {
        const estHd = hauteur >= 720;
        const qualiteLabel = hauteur ? `${hauteur}p` : (f['format_note'] || 'Vidéo');
        const tailleOctets = Number(f['filesize'] || f['filesize_approx'] || 50000000);
        const tailleMo = Math.round(tailleOctets / (1024 * 1024));

        formatsConstructes.push({
          identifiant: String(f['format_id'] || `v_${hauteur}p`),
          nomFormat: `Vidéo MP4 (${qualiteLabel})`,
          qualiteLabel: qualiteLabel,
          tailleEstimeeOctets: tailleOctets,
          tailleTexte: `~${tailleMo} MB`,
          typeContenu: 'video',
          extension: ext,
          estHauteDefinition: estHd,
          iconeNom: estHd ? 'high_quality' : 'sd',
          debitKbps: Number(f['tbr'] || 2000),
          urlTelechargement: String(urlTelecharge)
        });
      } else if (estAudioPur) {
        const debit = Math.round(Number(f['abr'] || 128));
        const tailleOctets = Number(f['filesize'] || f['filesize_approx'] || 15000000);
        const tailleMo = Math.round(tailleOctets / (1024 * 1024));

        formatsConstructes.push({
          identifiant: String(f['format_id'] || 'audio_mp3'),
          nomFormat: `Audio (${debit} kbps)`,
          qualiteLabel: `${debit} kbps Audio`,
          tailleEstimeeOctets: tailleOctets,
          tailleTexte: `~${tailleMo} MB`,
          typeContenu: 'audio',
          extension: ext === 'm4a' ? 'mp3' : ext,
          estHauteDefinition: debit >= 256,
          iconeNom: 'audio_file',
          debitKbps: debit,
          urlTelechargement: String(urlTelecharge)
        });
      }
    }

    formatsConstructes.sort((a, b) => b.tailleEstimeeOctets - a.tailleEstimeeOctets);

    return {
      identifiant,
      titre,
      auteur,
      dureeTexte,
      dureeSecondes,
      miniatureUrl,
      plateformeNom: 'YouTube',
      plateformeId: 'youtube',
      adresseOriginale: urlVideo,
      dateAjout: new Date().toISOString(),
      nombreVues,
      formats: formatsConstructes.slice(0, 8)
    };
  }

  private formaterDuree(secondes: number): string {
    const min = Math.floor(secondes / 60);
    const sec = Math.floor(secondes % 60);
    return `${min}:${sec < 10 ? '0' : ''}${sec}`;
  }
}
