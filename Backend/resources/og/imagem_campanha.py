#!/usr/bin/env python3
"""
Gera a imagem de pré-visualização (Open Graph, 1200x630) de uma campanha MPoints —
a que aparece ao partilhar o link no WhatsApp, Facebook, LinkedIn, Telegram, etc.

Entrada (JSON no stdin):
  {"nome": "...", "host": "bci.c.mpoints.site", "saida": "/caminho/imagem.png",
   "fonte_bold": "/caminho/Bold.ttf", "fonte": "/caminho/Regular.ttf"}

Chamado pelo App\\Services\\CampanhaImagemService. Os dados chegam por stdin (nunca pela
linha de comandos) e só se escreve no caminho de saída indicado.
"""
import json
import os
import sys
import tempfile

from PIL import Image, ImageDraw, ImageFont

LARGURA, ALTURA = 1200, 630

PRINCIPAL = (132, 4, 53)      # #840435
SECUNDARIA = (106, 3, 42)     # #6A032A
ROSA = (246, 184, 204)        # #f6b8cc
BRANCO = (255, 255, 255)
CINZA_CASA = (241, 234, 237)  # casa já aberta
TEXTO_CASA_ABERTA = (176, 150, 160)
DOURADO = (245, 179, 1)

MARGEM = 72
LARGURA_TEXTO = 600


def fonte(caminho, tamanho):
    return ImageFont.truetype(caminho, tamanho)


def largura(desenho, texto, f):
    caixa = desenho.textbbox((0, 0), texto, font=f)
    return caixa[2] - caixa[0]


def quebrar(desenho, texto, f, maximo):
    """Divide o texto em linhas que caibam em `maximo` píxeis (palavras longas são cortadas)."""
    linhas, atual = [], ''
    for palavra in texto.split():
        while largura(desenho, palavra, f) > maximo:
            corte = len(palavra)
            while corte > 1 and largura(desenho, palavra[:corte], f) > maximo:
                corte -= 1
            if atual:
                linhas.append(atual)
                atual = ''
            linhas.append(palavra[:corte])
            palavra = palavra[corte:]
        candidato = f'{atual} {palavra}'.strip()
        if largura(desenho, candidato, f) <= maximo:
            atual = candidato
        else:
            linhas.append(atual)
            atual = palavra
    if atual:
        linhas.append(atual)
    return linhas


def nome_legivel(nome):
    nome = ' '.join(str(nome or '').replace('_', ' ').split())
    return nome or 'Campanha MPoints'


def desenhar(dados):
    nome = nome_legivel(dados.get('nome'))
    host = str(dados.get('host') or '')[:80]
    f_bold, f_regular = dados['fonte_bold'], dados['fonte']

    img = Image.new('RGB', (LARGURA, ALTURA), PRINCIPAL)
    d = ImageDraw.Draw(img)

    # Diagonal da marca
    d.polygon([(780, 0), (LARGURA, 0), (LARGURA, ALTURA), (600, ALTURA)], fill=SECUNDARIA)

    # Grelha de números (o jogo), com uma casa premiada
    lado, espaco = 96, 16
    x0, y0 = 744, 150
    abertas = {1, 4, 6, 9}
    premiada = 7
    f_casa = fonte(f_bold, 30)
    for linha in range(3):
        for coluna in range(4):
            i = linha * 4 + coluna
            x = x0 + coluna * (lado + espaco)
            y = y0 + linha * (lado + espaco)
            numero = str(101 + i)
            if i == premiada:
                fundo, cor = DOURADO, SECUNDARIA
            elif i in abertas:
                fundo, cor = CINZA_CASA, TEXTO_CASA_ABERTA
            else:
                fundo, cor = BRANCO, PRINCIPAL
            d.rectangle([x, y, x + lado, y + lado], fill=fundo)
            # Número centrado na casa
            caixa = d.textbbox((0, 0), numero, font=f_casa)
            w, h = caixa[2] - caixa[0], caixa[3] - caixa[1]
            d.text((x + (lado - w) / 2 - caixa[0], y + (lado - h) / 2 - caixa[1]), numero, font=f_casa, fill=cor)

    # Marca
    f_marca = fonte(f_bold, 28)
    x = MARGEM
    for letra in 'MPOINTS':
        d.text((x, 64), letra, font=f_marca, fill=BRANCO)
        x += largura(d, letra, f_marca) + 6

    # Nome da campanha: o maior tamanho que caiba em até 3 linhas
    for tamanho in (72, 64, 58, 52, 46, 40, 36):
        f_nome = fonte(f_bold, tamanho)
        linhas = quebrar(d, nome, f_nome, LARGURA_TEXTO)
        if len(linhas) <= 3:
            break
    linhas = linhas[:3]
    if len(quebrar(d, nome, f_nome, LARGURA_TEXTO)) > 3:
        ultima = linhas[2]
        while ultima and largura(d, ultima + '…', f_nome) > LARGURA_TEXTO:
            ultima = ultima[:-1]
        linhas[2] = ultima.rstrip() + '…'

    altura_linha = int(tamanho * 1.12)
    bloco = altura_linha * len(linhas)
    y = 300 - bloco // 2
    for texto in linhas:
        d.text((MARGEM, y), texto, font=f_nome, fill=BRANCO)
        y += altura_linha

    # Frase do jogo
    f_frase = fonte(f_regular, 28)
    d.text((MARGEM, y + 22), 'Escolha um número e descubra se ganhou.', font=f_frase, fill=ROSA)

    # Link
    if host:
        f_link = fonte(f_bold, 24)
        while largura(d, host, f_link) > 620 and f_link.size > 16:
            f_link = fonte(f_bold, f_link.size - 2)
        d.text((MARGEM, ALTURA - 82), host, font=f_link, fill=ROSA)

    return img


def main():
    dados = json.loads(sys.stdin.read())
    saida = os.path.abspath(dados['saida'])
    pasta = os.path.dirname(saida)
    os.makedirs(pasta, exist_ok=True)

    img = desenhar(dados)

    # Escrita atómica: nunca deixa uma imagem a meio
    fd, temporario = tempfile.mkstemp(prefix='.og-', suffix='.png', dir=pasta)
    os.close(fd)
    try:
        img.save(temporario, 'PNG', optimize=True)
        os.chmod(temporario, 0o644)
        os.replace(temporario, saida)
    finally:
        if os.path.exists(temporario):
            os.unlink(temporario)
    print(saida)


if __name__ == '__main__':
    main()
