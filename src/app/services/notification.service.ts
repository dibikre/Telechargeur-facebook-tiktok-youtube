import { Injectable, signal } from '@angular/core';

export interface NotificationMessage {
  identifiant: string;
  texte: string;
  type: 'succes' | 'erreur' | 'info';
  dureeMilli: number;
}

@Injectable({
  providedIn: 'root'
})
export class ServiceNotification {
  public listeNotifications = signal<NotificationMessage[]>([]);

  public afficherSucces(message: string): void {
    this.ajouterNotification(message, 'succes');
  }

  public afficherErreur(message: string): void {
    this.ajouterNotification(message, 'erreur');
  }

  public afficherInformation(message: string): void {
    this.ajouterNotification(message, 'info');
  }

  private ajouterNotification(texte: string, type: 'succes' | 'erreur' | 'info'): void {
    const id = Math.random().toString(36).substring(2, 9);
    const nouvelle: NotificationMessage = {
      identifiant: id,
      texte,
      type,
      dureeMilli: 3500
    };

    this.listeNotifications.update(actuelles => [...actuelles, nouvelle]);

    setTimeout(() => {
      this.supprimerNotification(id);
    }, nouvelle.dureeMilli);
  }

  public supprimerNotification(id: string): void {
    this.listeNotifications.update(actuelles => actuelles.filter(n => n.identifiant !== id));
  }
}
