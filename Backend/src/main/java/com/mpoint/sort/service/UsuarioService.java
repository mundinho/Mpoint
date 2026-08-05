package com.mpoint.sort.service;

import com.mpoint.sort.dto.UsuarioDTO;
import reactor.core.publisher.Flux;
import reactor.core.publisher.Mono;

public interface UsuarioService {

    Flux<UsuarioDTO> listar();

    Mono<UsuarioDTO> buscarPorId(Long id);

    Mono<UsuarioDTO> criar(UsuarioDTO usuario);

    Mono<Void> remover(Long id);
}
