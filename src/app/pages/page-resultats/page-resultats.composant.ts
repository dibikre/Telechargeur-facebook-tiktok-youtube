import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';
import { MetadonneesMedia } from '../../modeles/media.modele';
import { OptionFormatMedia } from '../../modeles/format.modele';
import { ComposantCarteFormat } from '../../composants/carte-format/carte-format.composant';
import { ServiceTelechargement } from '../../services/telechargement.service';
import { ServiceExtracteurMedia } from '../../services/extracteur-media.service';

@Component({
  selector: 'app-page-resultats',
  standalone: true,
  imports: [MatIconModule, RouterLink, ComposantCarteFormat],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <main class="flex-grow flex flex-col items-center py-8 sm:py-12 md:py-20 px-4 sm:px-6 md:px-10 w-full max-w-7xl mx-auto animation-apparition">
      @if (mediaCourant(); as media) {
        <div class="w-full max-w-5xl bg-white dark:bg-surface-container-high rounded-2xl p-4 sm:p-6 md:p-10 border border-outline-variant/60 dark:border-outline shadow-xl">
          <div class="flex flex-col md:flex-row gap-6 sm:gap-8">
            
            <!-- Video Thumbnail Section -->
            <div class="w-full md:w-5/12 flex flex-col gap-3 sm:gap-4">
              <div class="aspect-video bg-surface-container-high rounded-xl overflow-hidden relative shadow-sm border border-outline-variant/30 group">
                <img [src]="media.miniatureUrl"
                     [alt]="media.titre"
                     referrerpolicy="no-referrer"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                
                <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                  <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-black/50 backdrop-blur-sm flex items-center justify-center text-white shadow-lg">
                    <mat-icon class="text-3xl sm:text-4xl leading-none">play_circle</mat-icon>
                  </div>
                </div>
              </div>

              <div class="flex flex-col">
                <h2 class="text-lg sm:text-xl font-bold text-on-surface dark:text-inverse-on-surface line-clamp-2 leading-snug">
                  {{ media.titre }}
                </h2>
                <p class="text-on-surface-variant dark:text-outline-variant text-xs font-semibold mt-1 sm:mt-2">
                  Durée: {{ media.dureeTexte }} • {{ media.auteur }}
                </p>
              </div>
            </div>

            <!-- Download Options Section -->
            <div class="w-full md:w-7/12 flex flex-col">
              <div class="flex items-center justify-between mb-4 sm:mb-6">
                <h3 class="text-xl sm:text-2xl font-black text-on-surface dark:text-inverse-on-surface tracking-tight">
                  Sélectionnez le format
                </h3>
                <span class="px-2.5 sm:px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold bg-primary/10 text-primary dark:text-inverse-primary uppercase">
                  {{ media.plateformeNom }}
                </span>
              </div>

              <div class="flex flex-col gap-3.5">
                @for (format of formatsDisponibles(); track format.identifiant) {
                  <app-carte-format
                    [formatMedia]="format"
                    (evenementTelecharger)="telechargerFormat($event)">
                  </app-carte-format>
                }
              </div>
            </div>

          </div>
        </div>

        <div class="mt-10 text-center">
          <a routerLink="/"
             class="inline-flex items-center gap-2 text-primary dark:text-inverse-primary hover:text-primary-container font-bold text-base transition-colors duration-200 group">
            <mat-icon class="group-hover:-rotate-90 transition-transform duration-300">refresh</mat-icon>
            <span>Convertir une autre vidéo</span>
          </a>
        </div>
      } @else {
        <div class="text-center py-20">
          <p class="text-lg text-on-surface-variant mb-4">Aucun média en cours d'analyse.</p>
          <a routerLink="/" class="bg-primary text-on-primary px-6 py-3 rounded-full font-semibold">
            Retour à l'accueil
          </a>
        </div>
      }
    </main>
  `
})
export class ComposantPageResultats implements OnInit {
  private routeur = inject(Router);
  private serviceTelechargement = inject(ServiceTelechargement);
  private serviceExtracteur = inject(ServiceExtracteurMedia);

  public mediaCourant = signal<MetadonneesMedia | null>(null);
  public formatsDisponibles = signal<OptionFormatMedia[]>([]);

  public ngOnInit(): void {
    if (typeof history !== 'undefined' && history.state && history.state.media) {
      this.mediaCourant.set(history.state.media);
      this.formatsDisponibles.set(history.state.formats || []);
    } else {
      // Charger un exemple par défaut si accès direct à la page /resultats
      const analyse = this.serviceExtracteur.analyserLienMedia("https://youtube.com/watch?v=demo_data_structures");
      if (analyse.media) {
        this.mediaCourant.set(analyse.media);
        this.formatsDisponibles.set(analyse.formatsDisponibles);
      }
    }
  }

  public telechargerFormat(format: OptionFormatMedia): void {
    const media = this.mediaCourant();
    if (media) {
      this.serviceTelechargement.lancerTelechargement(media, format);
    }
  }
}
