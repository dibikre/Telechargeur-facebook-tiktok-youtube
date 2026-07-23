import { ChangeDetectionStrategy, Component, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';
import { InformationPlateforme } from '../../modeles/plateforme.modele';

@Component({
  selector: 'app-page-sites-supportes',
  standalone: true,
  imports: [FormsModule, RouterLink, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <main class="flex-grow flex flex-col items-center py-12 md:py-20 px-4 md:px-10 max-w-7xl mx-auto w-full animation-apparition">
      <div class="text-center mb-12 max-w-3xl">
        <h1 class="text-3xl md:text-5xl font-black text-on-background dark:text-inverse-on-surface tracking-tight mb-4">
          Plateformes et Sites Supportés
        </h1>
        <p class="text-base md:text-lg text-on-surface-variant dark:text-outline-variant">
          MediaFetch prend en charge l'extraction rapide en haute définition sur plus de 50 plateformes majeures.
        </p>
      </div>

      <!-- Search Filter Input -->
      <div class="w-full max-w-md mb-10">
        <div class="relative">
          <mat-icon class="absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant">search</mat-icon>
          <input type="text"
                 [(ngModel)]="rechercheMotsCles"
                 placeholder="Rechercher une plateforme (ex: YouTube, TikTok)..."
                 class="w-full pl-11 pr-4 py-3 bg-surface-container-lowest dark:bg-inverse-surface border border-outline-variant/60 dark:border-outline rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
        </div>
      </div>

      <!-- Platforms Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
        @for (plateforme of listeFiltree(); track plateforme.identifiant) {
          <div class="bg-surface-container-lowest dark:bg-inverse-surface p-6 rounded-2xl border border-outline-variant/40 dark:border-outline shadow-sm hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-primary/10 dark:bg-primary/20 flex items-center justify-center text-primary dark:text-inverse-primary">
                    <mat-icon>{{ plateforme.iconeMat }}</mat-icon>
                  </div>
                  <h3 class="font-bold text-lg text-on-surface dark:text-inverse-on-surface">
                    {{ plateforme.nom }}
                  </h3>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                  Actif
                </span>
              </div>

              <p class="text-xs text-on-surface-variant dark:text-outline-variant mb-4 leading-relaxed">
                {{ plateforme.description }}
              </p>
            </div>

            <div>
              <div class="flex flex-wrap gap-1.5 mb-4">
                @for (fmt of plateforme.formatsInclus; track fmt) {
                  <span class="px-2 py-0.5 rounded bg-surface-container dark:bg-surface-container-high text-[11px] font-semibold text-on-surface-variant dark:text-outline-variant">
                    {{ fmt }}
                  </span>
                }
              </div>

              <a routerLink="/"
                 class="text-xs font-bold text-primary dark:text-inverse-primary hover:underline flex items-center gap-1">
                <span>Télécharger sur {{ plateforme.nom }}</span>
                <mat-icon class="text-sm">chevron_right</mat-icon>
              </a>
            </div>
          </div>
        }
      </div>
    </main>
  `
})
export class ComposantPageSitesSupportes {
  public rechercheMotsCles = signal<string>('');

  public plateformesOriginales: InformationPlateforme[] = [
    {
      identifiant: 'yt',
      nom: 'YouTube',
      iconeMat: 'play_circle',
      domaineExemple: 'youtube.com',
      couleurAccent: '#ff0000',
      description: 'Téléchargement de vidéos en 1080p, 2K, 4K, Shorts et extraction audio MP3 à 320 kbps.',
      formatsInclus: ['MP4 1080p', 'MP4 720p', 'MP3 320k', 'WebM 4K'],
      estActif: true
    },
    {
      identifiant: 'tt',
      nom: 'TikTok',
      iconeMat: 'music_note',
      domaineExemple: 'tiktok.com',
      couleurAccent: '#000000',
      description: 'Extraction directe des vidéos TikTok sans filigrane (Watermark) avec qualité originale.',
      formatsInclus: ['MP4 Sans Filigrane', 'MP3 Audio'],
      estActif: true
    },
    {
      identifiant: 'fb',
      nom: 'Facebook',
      iconeMat: 'facebook',
      domaineExemple: 'facebook.com',
      couleurAccent: '#1877f2',
      description: 'Support des vidéos publiques, Reels Facebook et directs enregistrés en haute qualité.',
      formatsInclus: ['MP4 HD', 'MP4 SD', 'MP3 Audio'],
      estActif: true
    },
    {
      identifiant: 'ig',
      nom: 'Instagram',
      iconeMat: 'camera_alt',
      domaineExemple: 'instagram.com',
      couleurAccent: '#e1306c',
      description: 'Sauvegarde des Reels, vidéos IGTV et publications avec carrousels.',
      formatsInclus: ['MP4 HD', 'JPG Miniature'],
      estActif: true
    },
    {
      identifiant: 'vm',
      nom: 'Vimeo',
      iconeMat: 'videocam',
      domaineExemple: 'vimeo.com',
      couleurAccent: '#1ab7ea',
      description: 'Extraction des vidéos haute performance Vimeo en résolutions 1080p et 4K.',
      formatsInclus: ['MP4 Full HD', 'MP4 720p'],
      estActif: true
    },
    {
      identifiant: 'tw',
      nom: 'X / Twitter',
      iconeMat: 'tag',
      domaineExemple: 'x.com',
      couleurAccent: '#1da1f2',
      description: 'Téléchargement rapide de vidéos et GIF intégrés aux tweets.',
      formatsInclus: ['MP4 720p', 'MP4 480p'],
      estActif: true
    }
  ];

  public listeFiltree = computed(() => {
    const terme = this.rechercheMotsCles().toLowerCase().trim();
    if (!terme) return this.plateformesOriginales;
    return this.plateformesOriginales.filter(p =>
      p.nom.toLowerCase().includes(terme) || p.description.toLowerCase().includes(terme)
    );
  });
}
