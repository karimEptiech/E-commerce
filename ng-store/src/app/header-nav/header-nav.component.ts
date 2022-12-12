import { Component, Inject, OnInit } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';
import { MatDialog } from '@angular/material/dialog';
import { UserService } from '../shared/service/user.service';

@Component({
  selector: 'app-header-nav',
  templateUrl: './header-nav.component.html',
  styleUrls: ['./header-nav.component.scss'],
})
export class HeaderNavComponent implements OnInit {
  constructor(public dialog: MatDialog) {}

  ngOnInit(): void {}

  public login(): void {
    const dialogRef = this.dialog.open(DialogLogin, {
      width: '250px',
    });
  }
}
@Component({
  selector: 'dialog-login',
  templateUrl: 'dialog-login.html',
})
export class DialogLogin {
  email = new FormControl('', [Validators.required, Validators.email]);
  password = new FormControl('', [Validators.required]);

  constructor(public userService: UserService) {}

  public onLog(): void {
    this.userService
      .login(this.email.value, this.password.value)
      .subscribe((response) => {
        console.log(response);
      });
  }
}
