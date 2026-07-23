import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { ComposantBarreNavigation } from './composants/barre-navigation/barre-navigation.composant';
import { ComposantPiedDePage } from './composants/pied-de-page/pied-de-page.composant';
import { ComposantModalConnexion } from './composants/modal-connexion/modal-connexion.composant';
import { ComposantBarreNotifications } from './composants/barre-notifications/barre-notifications.composant';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    RouterOutlet,
    ComposantBarreNavigation,
    ComposantPiedDePage,
    ComposantModalConnexion,
    ComposantBarreNotifications
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App {
  public estModalConnexionOuverte = signal<boolean>(false);

  public ouvrirModalConnexion(): void {
    this.estModalConnexionOuverte.set(true);
  }

  public fermerModalConnexion(): void {
    this.estModalConnexionOuverte.set(false);
  }
}
