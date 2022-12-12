import { Component, OnInit } from '@angular/core';
import { Product } from '../shared/models/product.model';
import { ProductsService } from '../shared/service/products.service';

@Component({
  selector: 'app-product-list',
  templateUrl: './product-list.component.html',
  styleUrls: ['./product-list.component.scss'],
})
export class ProductListComponent implements OnInit {
  public productList: Product[] = [];
  constructor(private productService: ProductsService) {}

  ngOnInit(): void {
    this.getProductList();
  }

  public getProductList(): void {
    this.productService.getProductList().subscribe((products) => {
      this.productList = products;
      console.log(this.productList);
    });
  }
}
