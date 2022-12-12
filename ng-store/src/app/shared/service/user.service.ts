import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root',
})
export class UserService {
  private urlLogin = `${environment.apiUrl}/alogin`;
  constructor(private http: HttpClient) {}

  public login(email: string, password: string): Observable<object> {
    return this.http.post<object>(this.urlLogin, {
      obj: {
        email: email,
        password: password,
      },
    });
  }
}
