import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Product } from 'src/app/shared/models/product.model';

@Injectable({
  providedIn: 'root',
})
export class ProductsService {
  private urlProducts = `${environment.apiUrl}/products`;

  constructor(private http: HttpClient) {}

  public getProductList(): Observable<Product[]> {
    return this.http.get<Product[]>(this.urlProducts);
  }
}
