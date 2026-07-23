import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';
import { ComposantCarteEtape } from '../../composants/carte-etape/carte-etape.composant';

@Component({
  selector: 'app-page-comment-utiliser',
  standalone: true,
  imports: [RouterLink, MatIconModule, ComposantCarteEtape],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <main class="flex-grow flex flex-col items-center justify-center px-4 md:px-10 py-16 md:py-24 max-w-7xl mx-auto w-full animation-apparition">
      
      <div class="text-center mb-16 max-w-3xl">
        <h1 class="text-3xl md:text-5xl font-black text-on-background dark:text-inverse-on-surface tracking-tight mb-4">
          Comment télécharger des vidéos
        </h1>
        <p class="text-base md:text-lg text-on-surface-variant dark:text-outline-variant font-normal">
          Obtenez vos médias préférés hors ligne en trois étapes simples et rapides.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 w-full">
        <app-carte-etape
          iconeMat="content_copy"
          titreEtape="1. Copiez le lien"
          descriptionEtape="Trouvez la vidéo que vous souhaitez télécharger sur votre plateforme préférée et copiez son URL (lien) dans le presse-papiers.">
        </app-carte-etape>

        <app-carte-etape
          iconeMat="content_paste"
          titreEtape="2. Collez-le"
          descriptionEtape="Rendez-vous sur la page d'accueil de MicMediaFetch et collez l'URL copiée dans le grand champ de saisie central.">
        </app-carte-etape>

        <app-carte-etape
          iconeMat="download"
          titreEtape="3. Téléchargez"
          descriptionEtape="Sélectionnez la qualité vidéo ou audio souhaitée (ex: 1080p, MP3) parmi les options générées, puis cliquez sur télécharger.">
        </app-carte-etape>
      </div>

      <div class="mt-16 text-center">
        <a routerLink="/"
           class="inline-flex items-center justify-center bg-primary text-on-primary px-8 py-4 rounded-xl font-semibold text-base shadow-lg hover:shadow-xl hover:bg-primary-container hover:text-on-primary-container hover:-translate-y-0.5 active:scale-95 transition-all duration-200 gap-2">
          <span>Essayer maintenant</span>
          <mat-icon>arrow_forward</mat-icon>
        </a>
      </div>

    </main>
  `
})
export class ComposantPageCommentUtiliser {}
