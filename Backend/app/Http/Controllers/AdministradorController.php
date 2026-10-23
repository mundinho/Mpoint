<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\Campanha;
use App\Services\AuditoriaService;
use App\Support\Telefone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Gestão dos administradores do painel (só o administrador master).
 */
class AdministradorController extends Controller
{
    public function __construct(private AuditoriaService $auditoria)
    {
    }

    public function index(): JsonResponse
    {
        $administradores = Administrador::orderByDesc('master')->orderBy('nome')->get();

        return response()->json([
            'administradores' => $administradores->map(fn (Administrador $a) => $a->perfil()),
            'campanhas' => Campanha::orderByDesc('id')->get(['id', 'nome', 'slug', 'estado'])
                ->map(fn (Campanha $c) => [
                    'id' => $c->id,
                    'nome' => $c->nome,
                    'slug' => $c->slug,
                    'estado' => $c->estado,
                ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $this->validar($request);

        $administrador = DB::transaction(function () use ($dados) {
            $administrador = Administrador::create([
                'nome' => $dados['nome'],
                'telefone' => $dados['telefone'],
                'ativo' => $dados['ativo'] ?? true,
                'master' => $dados['master'] ?? false,
                'todas_campanhas' => $dados['todas_campanhas'] ?? false,
            ]);

            $this->sincronizarCampanhas($administrador, $dados);

            return $administrador;
        });

        $this->registar($request, 'criar_administrador', $administrador);

        return response()->json($administrador->perfil(), 201);
    }

    public function update(Request $request, Administrador $administrador): JsonResponse
    {
        $dados = $this->validar($request, $administrador);
        $proprio = $request->attributes->get('administrador')->is($administrador);

        // O master não pode tirar a si próprio o acesso (evita ficar sem ninguém para gerir)
        if ($proprio && (($dados['master'] ?? true) === false || ($dados['ativo'] ?? true) === false)) {
            return response()->json(['message' => 'Não pode retirar o seu próprio acesso de master nem desactivar a sua conta.'], 422);
        }

        DB::transaction(function () use ($administrador, $dados) {
            $administrador->update([
                'nome' => $dados['nome'],
                'telefone' => $dados['telefone'],
                'ativo' => $dados['ativo'] ?? $administrador->ativo,
                'master' => $dados['master'] ?? $administrador->master,
                'todas_campanhas' => $dados['todas_campanhas'] ?? $administrador->todas_campanhas,
            ]);

            // Conta desactivada: termina a sessão aberta
            if (!$administrador->ativo) {
                $administrador->update(['api_token' => null, 'token_expira_em' => null]);
            }

            $this->sincronizarCampanhas($administrador, $dados);
        });

        $this->registar($request, 'actualizar_administrador', $administrador);

        return response()->json($administrador->fresh()->perfil());
    }

    public function destroy(Request $request, Administrador $administrador): JsonResponse
    {
        if ($request->attributes->get('administrador')->is($administrador)) {
            return response()->json(['message' => 'Não pode remover a sua própria conta.'], 422);
        }

        $this->registar($request, 'remover_administrador', $administrador);

        // Os SMS enviados a este administrador ficam no histórico sem ligação
        DB::transaction(function () use ($administrador) {
            DB::table('sms')->where('administrador_id', $administrador->id)->update(['administrador_id' => null]);
            $administrador->delete();
        });

        return response()->json(['message' => 'Administrador removido.']);
    }

    private function validar(Request $request, ?Administrador $administrador = null): array
    {
        try {
            $request->merge(['telefone' => Telefone::normalizar($request->input('telefone'))]);
        } catch (\InvalidArgumentException $e) {
            abort(response()->json(['message' => $e->getMessage(), 'errors' => ['telefone' => [$e->getMessage()]]], 422));
        }

        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => [
                'required', 'string', 'max:20',
                Rule::unique('administradores', 'telefone')->ignore($administrador?->id),
            ],
            'ativo' => ['sometimes', 'boolean'],
            'master' => ['sometimes', 'boolean'],
            'todas_campanhas' => ['sometimes', 'boolean'],
            'campanhas' => ['sometimes', 'array'],
            'campanhas.*' => ['integer', 'distinct', 'exists:campanha,id'],
        ], [
            'telefone.unique' => 'Já existe um administrador com este número.',
            'campanhas.*.exists' => 'Uma das campanhas seleccionadas não existe.',
        ]);
    }

    private function sincronizarCampanhas(Administrador $administrador, array $dados): void
    {
        if ($administrador->acessoTotal()) {
            $administrador->campanhas()->detach();
        } elseif (array_key_exists('campanhas', $dados)) {
            $administrador->campanhas()->sync($dados['campanhas']);
        }
    }

    private function registar(Request $request, string $acao, Administrador $alvo): void
    {
        $autor = $request->attributes->get('administrador');
        $perfil = $alvo->perfil();
        $acesso = $perfil['master'] ? 'master' : ($perfil['todas_campanhas'] ? 'todas as campanhas' : 'campanhas [' . implode(',', $perfil['campanhas']) . ']');

        $this->auditoria->registrar('Administrador', $acao, true, "Administrador {$autor->id} ({$autor->nome}): {$acao} {$alvo->id} ({$alvo->nome}) — acesso: {$acesso}.");
    }
}
