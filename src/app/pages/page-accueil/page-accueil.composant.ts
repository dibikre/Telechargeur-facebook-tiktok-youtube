import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';
import { ComposantBarreRecherche } from '../../composants/barre-recherche/barre-recherche.composant';
import { ComposantCarteCaracteristique } from '../../composants/carte-caracteristique/carte-caracteristique.composant';
import { ServiceExtracteurMedia } from '../../services/extracteur-media.service';
import { ServiceTelechargement } from '../../services/telechargement.service';
import { ServiceNotification } from '../../services/notification.service';

@Component({
  selector: 'app-page-accueil',
  standalone: true,
  imports: [
    MatIconModule,
    ComposantBarreRecherche,
    ComposantCarteCaracteristique
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <main class="flex-grow flex flex-col items-center justify-center px-4 md:px-10 py-12 md:py-20 max-w-7xl mx-auto w-full">
      <!-- Badge & Hero -->
      <div class="text-center mb-10 max-w-3xl animation-apparition">
        <h1 class="text-4xl md:text-6xl font-extrabold text-[#0000FF] tracking-tight mb-6 leading-tight">
          Téléchargeur de Vidéos Gratuit
        </h1>

        <p class="text-lg md:text-xl text-on-surface-variant dark:text-outline-variant font-normal leading-relaxed">
          Téléchargez des vidéos depuis YouTube, TikTok, Facebook et des centaines d'autres sites en haute qualité. Sans inscription.
        </p>
      </div>

      <!-- Search Box -->
      <div class="w-full mb-16">
        <app-barre-recherche (evenementSoumissionUrl)="traiterSoumissionUrl($event)"></app-barre-recherche>
      </div>

      <!-- Progress Overlay if Download Active -->
      @if (serviceTelechargement.telechargementEnCours(); as progression) {
        <div class="w-full max-w-2xl bg-white dark:bg-surface-container-high border border-outline-variant/60 dark:border-outline p-6 rounded-2xl shadow-xl mb-12 animation-apparition">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <mat-icon class="text-primary animate-spin">sync</mat-icon>
              <span class="font-bold text-sm text-on-surface dark:text-inverse-on-surface">Téléchargement en cours...</span>
            </div>
            <span class="font-black text-primary text-sm">{{ progression.pourcentage }}%</span>
          </div>

          <div class="w-full h-3 bg-surface-container dark:bg-surface-container-high rounded-full overflow-hidden">
            <div class="h-full bg-primary transition-all duration-300 rounded-full"
                 [style.width.%]="progression.pourcentage"></div>
          </div>

          <div class="flex justify-between items-center text-xs text-on-surface-variant dark:text-outline-variant mt-2 font-medium">
            <span>{{ (progression.octetsTelecharges / 1000000).toFixed(1) }} MB / {{ (progression.octetsTotaux / 1000000).toFixed(1) }} MB</span>
            <span>Vitesse: {{ progression.vitesseKo }} KB/s</span>
          </div>
        </div>
      }

      <!-- Features Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 w-full mt-4">
        <app-carte-caracteristique
          iconeMat="high_quality"
          titreCaracteristique="Haute Qualité"
          descriptionCaracteristique="Téléchargez vos vidéos préférées dans la meilleure résolution disponible, jusqu'en 4K et 8K.">
        </app-carte-caracteristique>

        <app-carte-caracteristique
          iconeMat="bolt"
          titreCaracteristique="Rapide et Gratuit"
          descriptionCaracteristique="Notre service est ultra-rapide, 100% gratuit et sans limite de téléchargement quotidienne.">
        </app-carte-caracteristique>

        <app-carte-caracteristique
          iconeMat="security"
          titreCaracteristique="Sécurisé"
          descriptionCaracteristique="Votre confidentialité est garantie. Aucun logiciel à installer, pas de malwares, pas de suivi.">
        </app-carte-caracteristique>
      </div>
    </main>
  `
})
export class ComposantPageAccueil {
  private serviceExtracteur = inject(ServiceExtracteurMedia);
  public serviceTelechargement = inject(ServiceTelechargement);
  private serviceNotification = inject(ServiceNotification);
  private routeur = inject(Router);

  public traiterSoumissionUrl(adresseUrl: string): void {
    const resultat = this.serviceExtracteur.analyserLienMedia(adresseUrl);
    if (resultat.estValide && resultat.media) {
      this.routeur.navigate(['/resultats'], {
        state: {
          media: resultat.media,
          formats: resultat.formatsDisponibles
        }
      });
    } else {
      this.serviceNotification.afficherErreur(resultat.messageErreur || "Impossible d'analyser ce lien.");
    }
  }
}
