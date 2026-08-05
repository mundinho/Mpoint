package com.mpoint.sort.service.impl;

import com.mpoint.sort.dto.UsuarioDTO;
import com.mpoint.sort.model.Usuario;
import com.mpoint.sort.repository.UsuarioRepository;
import com.mpoint.sort.service.UsuarioService;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import reactor.core.publisher.Flux;
import reactor.core.publisher.Mono;

@Service
@RequiredArgsConstructor
public class UsuarioServiceImpl implements UsuarioService {

    private final UsuarioRepository usuarioRepository;

    @Override
    public Flux<UsuarioDTO> listar() {
        return usuarioRepository.findAll().map(this::toDTO);
    }

    @Override
    public Mono<UsuarioDTO> buscarPorId(Long id) {
        return usuarioRepository.findById(id).map(this::toDTO);
    }

    @Override
    public Mono<UsuarioDTO> criar(UsuarioDTO usuario) {
        Usuario entidade = new Usuario(null, usuario.nome(), usuario.telefone());
        return usuarioRepository.save(entidade).map(this::toDTO);
    }

    @Override
    public Mono<Void> remover(Long id) {
        return usuarioRepository.deleteById(id);
    }

    private UsuarioDTO toDTO(Usuario usuario) {
        return new UsuarioDTO(usuario.getId(), usuario.getNome(), usuario.getTelefone());
    }
}
