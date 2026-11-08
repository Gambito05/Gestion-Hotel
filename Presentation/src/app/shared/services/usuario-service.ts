import { inject, Injectable,signal } from '@angular/core';
import {UsuarioModel} from '../models/usuario-model';
import { HttpClient, httpResource } from '@angular/common/http';
import { environment } from '../../../environments/environment';

 type ParametroUsuario = Record<string, string | number | boolean>;

 const _SERVIDOR = environment.servidor;

 interface RespuestaUsuario {
    data: UsuarioModel[];
    pagination:{
          offset: number;
            limit: number;
            total: number;
    };

 }
@Injectable({providedIn: 'root'})
export class UsuarioService {
   private readonly http = inject(HttpClient);
   private readonly parametros = signal<ParametroUsuario | null>(null);

   readonly usuarios = httpResource<RespuestaUsuario>(()=>{
    const parametros = this.parametros();
    if (parametros === null){
        return undefined;
    }
    return{
        url: `${_SERVIDOR}/usuarios/filter/0/10`,
        params: parametros,
    };
 });
 filtrar(parametros: ParametroUsuario): void{
    this.parametros.set(parametros);
 }
 guardar(datos: UsuarioModel, id? : number){
      delete datos.id;

      if(id){//Modificando al usuario
         return this.http.put<any>(`${_SERVIDOR}/usuarios/${id}`, datos);
      }
       else{//Nuevo usuario
         return this.http.post<any>(`${_SERVIDOR}/usuarios`, datos);
       }
  }
}
