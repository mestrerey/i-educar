<?php
//error_reporting(E_ERROR);
//ini_set("display_errors", 1);
/**
 * i-Educar - Sistema de gestão escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itajaí
 *     <ctima@itajai.sc.gov.br>
 *
 * Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a versão 2 da Licença, como (a seu critério)
 * qualquer versão posterior.
 *
 * Este programa é distribuí­do na expectativa de que seja útil, porém, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia implí­cita de COMERCIABILIDADE OU
 * ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a Licença Pública Geral
 * do GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral do GNU junto
 * com este programa; se não, escreva para a Free Software Foundation, Inc., no
 * endereço 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author     Lucas Schmoeller da Silva <lucas@portabilis.com.br>
 * @category   i-Educar
 * @license    @@license@@
 * @package    Api
 * @subpackage Modules
 * @since      Arquivo disponível desde a versão ?
 * @version    $Id$
 */

require_once 'Portabilis/Controller/ApiCoreController.php';
require_once 'Portabilis/Array/Utils.php';
require_once 'Portabilis/String/Utils.php';
require_once 'Portabilis/Array/Utils.php';
require_once 'Portabilis/Date/Utils.php';
require_once 'include/pmieducar/geral.inc.php';

class PreMatriculaController extends ApiCoreController
{
  protected function canHomologarPreMatricula(){
    return $this->validatesPresenceOf('ano_letivo') && $this->validatesPresenceOf('curso_id')
        && $this->validatesPresenceOf('serie_id') && $this->validatesPresenceOf('escola_id')
        && $this->validatesPresenceOf('turma_id') && $this->validatesPresenceOf('nome_aluno')
        && $this->validatesPresenceOf('data_nasc_aluno') && $this->validatesPresenceOf('sexo_aluno')
        && $this->validatesPresenceOf('cep') && $this->validatesPresenceOf('rua')
        && $this->validatesPresenceOf('numero') && $this->validatesPresenceOf('bairro')
        && $this->validatesPresenceOf('cidade') && $this->validatesPresenceOf('estado') && $this->validatesPresenceOf('pais')
        && $this->validatesPresenceOf('matricula_id');
  }

  protected function homologarPreMatricula(){
    if($this->canHomologarPreMatricula()){
  	  // Dados da matrícula
      $anoLetivo = $this->getRequest()->ano_letivo;
      $cursoId = $this->getRequest()->curso_id;
      $serieId = $this->getRequest()->serie_id;
      $escolaId = $this->getRequest()->escola_id;
      $turmaId = $this->getRequest()->turma_id;
      $matriculaId = $this->getRequest()->matricula_id;

      // Dados do aluno
      $nomeAluno = Portabilis_String_utils::toLatin1($this->getRequest()->nome_aluno);
      $dataNascAluno = $this->getRequest()->data_nasc_aluno;
      $deficiencias = $this->getRequest()->deficiencias;
      $sexoAluno = $this->getRequest()->sexo_aluno;
      $alunoIdParametro = $this->getRequest()->aluno_id;

      // Dados responsaveis
      $nomeMae = Portabilis_String_utils::toLatin1($this->getRequest()->nome_mae);
      $cpfMae = $this->getRequest()->cpf_mae;
      $nomeResponsavel = Portabilis_String_utils::toLatin1($this->getRequest()->nome_responsavel);
      $cpfResponsavel = $this->getRequest()->cpf_responsavel;

      // Dados do endereço
      $cep = $this->getRequest()->cep;
      $rua = Portabilis_String_utils::toLatin1($this->getRequest()->rua);
      $numero = $this->getRequest()->numero;
      $complemento = Portabilis_String_utils::toLatin1($this->getRequest()->complemento);
      $bairro = Portabilis_String_utils::toLatin1($this->getRequest()->bairro);
      $cidade = Portabilis_String_utils::toLatin1($this->getRequest()->cidade);
      $estado = Portabilis_String_utils::toLatin1($this->getRequest()->estado);
      $pais = Portabilis_String_utils::toLatin1($this->getRequest()->pais);

      $matriculaId = $this->getRequest()->matricula_id;

      $obj_m = new clsPmieducarMatricula($matriculaId);

      $det_m = $obj_m->detalhe();
      $alunoIdMatricula = $det_m['ref_cod_aluno'];

      if($det_m['aprovado'] != 11){
      	$this->messenger->append("Matrícula já homologada.");
      	return array("cod_matricula" => 0);
      }

	  // $this->messenger->append("max alunos turma: " . $this->_maxAlunosTurma($turmaId) . "alunos matriculados na turma: " . $this->_alunosMatriculadosTurma($turmaId));
      if($this->_maxAlunosTurma($turmaId) <= $this->_alunosMatriculadosTurma($turmaId)){
	    $this->messenger->append("Aparentemente não existem vagas disponíveis para a seleção informada. Altere a seleção e tente novamente.");
	    return array("cod_matricula" => 0);
	   }

	   if($alunoIdParametro){
	   	 $obj_a = new clsPmieducarAluno($alunoIdParametro);
	   	 if($obj_a->detalhe()){

	     	$obj_m = new clsPmieducarMatricula($matriculaId);
	  		$obj_m->ref_cod_aluno = $alunoIdParametro;
	  		$obj_m->edita();

	  		$obj_a->ativo = 1;
	  		$obj_a->edita();

        if($alunoIdParametro != $alunoIdMatricula){
	  		 $this->excluirInformacoesAluno($alunoIdMatricula);
        }

	  		return array("cod_matricula" => $this->enturmaPreMatricula($alunoIdParametro, $turmaId, $matriculaId, false));
	 	  }
	   }

      $obj_a = new clsPmieducarAluno($alunoIdMatricula);
      $det_a = $obj_a->detalhe();
      $pessoaAlunoId = $det_a['ref_idpes'];

      $pessoa        = new clsPessoa_($pessoaAlunoId);
      $pessoa->nome  = addslashes($nomeAluno);
      $pessoa->tipo  = 'F';
      $pessoa->edita();

      $pessoaMaeId = null;
      $pessoaResponsavelId = null;

      $pessoaFisicaAluno = new clsFisica($pessoaAlunoId);
      $pessoaFisicaAluno_det = $pessoaFisicaAluno->detalhe();

      $pessoaMaeId = $pessoaFisicaAluno_det['idpes_mae'];
      $pessoaResponsavelId = $pessoaFisicaAluno_det['idpes_responsavel'];
      $maeIsResponsavel = ($pessoaMaeId == $pessoaResponsavelId);

      if(is_numeric($pessoaMaeId)){
      	$pessoaMaeAluno = new clsPessoa_($pessoaMaeId);
      	$pessoaMaeAluno->nome = $nomeMae;
      	$pessoaMaeAluno->edita();

      	$pessoaFisicaMaeAluno = new clsFisica($pessoaMaeId);
      	$pessoaFisicaMaeAluno->cpf = $cpfMae;
      	$pessoaFisicaMaeAluno->idpes_rev = 1;
      	$pessoaFisicaMaeAluno->edita();
      }
      elseif(is_numeric($cpfMae)){
        $pessoaMaeId = $this->createOrUpdatePessoaResponsavel($cpfMae, $nomeMae);
        $this->createOrUpdatePessoaFisicaResponsavel($pessoaMaeId, $cpfMae);
      }
      if(!$maeIsResponsavel){
      	if(is_numeric($pessoaResponsavelId)){
      		$pessoaResponsavelAluno = new clsPessoa_($pessoaResponsavelId);
      		$pessoaResponsavelAluno->nome = $nomeResponsavel;
      		$pessoaResponsavelAluno->edita();

	      	$pessoaFisicaResponsavelAluno = new clsFisica($pessoaResponsavelId);
	      	$pessoaFisicaResponsavelAluno->cpf = $cpfResponsavel;
	      	$pessoaFisicaResponsavelAluno->idpes_rev = 1;
      		$pessoaFisicaResponsavelAluno->edita();
      	}
      	elseif(is_numeric($cpfResponsavel)){
        	$pessoaResponsavelId = $this->createOrUpdatePessoaResponsavel($cpfResponsavel, $nomeResponsavel);
        	$this->createOrUpdatePessoaFisicaResponsavel($pessoaResponsavelId, $cpfResponsavel);
      	}
      }

      $this->createOrUpdatePessoaFisica($pessoaAlunoId, $pessoaResponsavelId, $pessoaMaeId, $dataNascAluno, $sexoAluno);

      $alunoId = $this->createOrUpdateAluno($pessoaAlunoId, 1);

      $this->updateDeficiencias($pessoaAlunoId, $deficiencias);

	  $this->createOrUpdateEndereco($pessoaAlunoId, $cep, $rua, $numero, $complemento, $bairro, $cidade, $estado, $pais);

  	  // $this->messenger->append("escola:" . $escolaId . " serie:" . $serieId . " anoletivo:" . $anoLetivo .
  	  		                    // " curso: " . $cursoId . " aluno:" . $alunoId . " turma: " . $turmaId . "matricula: " . $matriculaId);

      return array("cod_matricula" => $this->enturmaPreMatricula($alunoIdMatricula, $turmaId, $matriculaId, $maeIsResponsavel));

	}
}
  protected function canRegistrarPreMatricula(){
    return $this->validatesPresenceOf('ano_letivo') && $this->validatesPresenceOf('curso_id')
        && $this->validatesPresenceOf('serie_id') && $this->validatesPresenceOf('escola_id')
        && $this->validatesPresenceOf('turno_id') && $this->validatesPresenceOf('nome_aluno')
        && $this->validatesPresenceOf('data_nasc_aluno') && $this->validatesPresenceOf('sexo_aluno');
  }

  protected function registrarPreMatricula(){
    if ($this->canRegistrarPreMatricula()){
      // Dados da matrícula
      $anoLetivo = $this->getRequest()->ano_letivo;
      $cursoId = $this->getRequest()->curso_id;
      $serieId = $this->getRequest()->serie_id;
      $escolaId = $this->getRequest()->escola_id;
      $turnoId = $this->getRequest()->turno_id;

      $qtdFila = $this->_getQtdAlunosFila($anoLetivo, $escolaId, $cursoId, $serieId, $turnoId);
      $maxAlunoTurno = $this->_getMaxAlunoTurno($anoLetivo, $escolaId, $serieId, $turnoId);
      $qtdMatriculaTurno = $this->_getQtdMatriculaTurno($anoLetivo, $escolaId, $cursoId, $serieId, $turnoId);

      if($maxAlunoTurno <= $qtdFila + $qtdMatriculaTurno){
        // $this->messenger->append("Quantidade de reservas: ".$qtdFila.". Máximo de alunos permitido no turno: ".$maxAlunoTurno.". Quantidade de alunos matriculados no turno: ".$qtdMatriculaTurno);
        $this->messenger->append("Aparentemente não existem vagas disponíveis para a seleção informada. Altere a seleção e tente novamente.");
        return array("cod_matricula" => 0);
      }

      // Dados do aluno
      $nomeAluno = Portabilis_String_utils::toLatin1($this->getRequest()->nome_aluno);
      $dataNascAluno = $this->getRequest()->data_nasc_aluno;
      $deficiencias = $this->getRequest()->deficiencias;
      $sexoAluno = $this->getRequest()->sexo_aluno;

      // Dados responsaveis
      $nomeMae = Portabilis_String_utils::toLatin1($this->getRequest()->nome_mae);
      $cpfMae = $this->getRequest()->cpf_mae;
      $nomeResponsavel = Portabilis_String_utils::toLatin1($this->getRequest()->nome_responsavel);
      $cpfResponsavel = $this->getRequest()->cpf_responsavel;

      $pessoaAlunoId = $this->createPessoa($nomeAluno);
      $pessoaMaeId = null;
      $pessoaResponsavelId = null;

      if(is_numeric($cpfMae)){
        $pessoaMaeId = $this->createOrUpdatePessoaResponsavel($cpfMae, $nomeMae);
        $this->createOrUpdatePessoaFisicaResponsavel($pessoaMaeId, $cpfMae);
      }

      if(is_numeric($cpfResponsavel)){
        $pessoaResponsavelId = $this->createOrUpdatePessoaResponsavel($cpfResponsavel, $nomeResponsavel);
        $this->createOrUpdatePessoaFisicaResponsavel($pessoaResponsavelId, $cpfResponsavel);
      }

      $this->createOrUpdatePessoaFisica($pessoaAlunoId, $pessoaResponsavelId, $pessoaMaeId, $dataNascAluno, $sexoAluno);

      $alunoId = $this->createOrUpdateAluno($pessoaAlunoId, 0);

      if(is_array($deficiencias))
        $this->updateDeficiencias($pessoaAlunoId, $deficiencias);

      return array("cod_matricula" => $this->cadastraPreMatricula($escolaId, $serieId, $anoLetivo, $cursoId, $alunoId, $turnoId));
    }
  }

  function _getMaxAlunoTurno($ano, $escolaId, $serieId, $turnoId){
    $obj_t = new clsPmieducarTurma();

    $lista_t = $obj_t->lista($int_cod_turma = null, $int_ref_usuario_exc = null, $int_ref_usuario_cad = null,
    $int_ref_ref_cod_serie = $serieId, $int_ref_ref_cod_escola = $escolaId, $int_ref_cod_infra_predio_comodo = null,
    $str_nm_turma = null, $str_sgl_turma = null, $int_max_aluno = null, $int_multiseriada = null, $date_data_cadastro_ini = null,
    $date_data_cadastro_fim = null, $date_data_exclusao_ini = null, $date_data_exclusao_fim = null, $int_ativo = null, $int_ref_cod_turma_tipo = null,
    $time_hora_inicial_ini = null, $time_hora_inicial_fim = null, $time_hora_final_ini = null, $time_hora_final_fim = null, $time_hora_inicio_intervalo_ini = null,
    $time_hora_inicio_intervalo_fim = null, $time_hora_fim_intervalo_ini = null, $time_hora_fim_intervalo_fim = null, $int_ref_cod_curso = null, $int_ref_cod_instituicao = null,
    $int_ref_cod_regente = null, $int_ref_cod_instituicao_regente = null, $int_ref_ref_cod_escola_mult = null, $int_ref_ref_cod_serie_mult = null, $int_qtd_min_alunos_matriculados = null,
    $bool_verifica_serie_multiseriada = false, $bool_tem_alunos_aguardando_nota = null, $visivel = null, $turma_turno_id = $turnoId, $tipo_boletim = null, $ano = $ano, $somenteAnoLetivoEmAndamento = FALSE);

    $max_aluno_turmas = 0;

    foreach ($lista_t as $reg) {
      $max_aluno_turmas += $reg['max_aluno'];
    }

    return $max_aluno_turmas;
  }

  function _getQtdAlunosFila($ano, $escolaId, $cursoId, $serieId, $turnoId){

    $sql = 'SELECT count(1) as qtd
              FROM pmieducar.matricula
              WHERE ano = $1
              AND ref_ref_cod_escola = $2
              AND ref_cod_curso = $3
              AND ref_ref_cod_serie = $4
              AND turno_pre_matricula = $5
              AND aprovado = 11 ';

    return (int) Portabilis_Utils_Database::selectField($sql, array($ano, $escolaId, $cursoId, $serieId, $turnoId));
  }

  function _getQtdMatriculaTurno($ano, $escolaId, $cursoId, $serieId, $turnoId){
    $obj_mt = new clsPmieducarMatriculaTurma();

    return (int) count($obj_mt->lista($int_ref_cod_matricula = NULL, $int_ref_cod_turma = NULL,
              $int_ref_usuario_exc = NULL, $int_ref_usuario_cad = NULL,
              $date_data_cadastro_ini = NULL, $date_data_cadastro_fim = NULL,
              $date_data_exclusao_ini = NULL, $date_data_exclusao_fim = NULL, $int_ativo = 1,
              $int_ref_cod_serie = $serieId, $int_ref_cod_curso = $cursoId, $int_ref_cod_escola = $escolaId,
              $int_ref_cod_instituicao = NULL, $int_ref_cod_aluno = NULL, $mes = NULL,
              $aprovado = NULL, $mes_menor_que = NULL, $int_sequencial = NULL,
              $int_ano_matricula = NULL, $tem_avaliacao = NULL, $bool_get_nome_aluno = FALSE,
              $bool_aprovados_reprovados = NULL, $int_ultima_matricula = NULL,
              $bool_matricula_ativo = NULL, $bool_escola_andamento = FALSE,
              $mes_matricula_inicial = FALSE, $get_serie_mult = FALSE,
              $int_ref_cod_serie_mult = NULL, $int_semestre = NULL,
              $pegar_ano_em_andamento = FALSE, $parar=NULL, $diario = FALSE,
              $int_turma_turno_id = $turnoId, $int_ano_turma = $ano));
  }

  /*protected function canMatricularCandidato(){
    return $this->validatesPresenceOf('ano_letivo') && $this->validatesPresenceOf('curso_id')
        && $this->validatesPresenceOf('serie_id') && $this->validatesPresenceOf('escola_id')
        && $this->validatesPresenceOf('turma_id') && $this->validatesPresenceOf('nome_aluno')
        && $this->validatesPresenceOf('data_nasc_aluno') && $this->validatesPresenceOf('sexo_aluno')
        && $this->validatesPresenceOf('cep') && $this->validatesPresenceOf('rua')
        && $this->validatesPresenceOf('numero') && $this->validatesPresenceOf('bairro')
        && $this->validatesPresenceOf('cidade') && $this->validatesPresenceOf('estado') && $this->validatesPresenceOf('pais');
  }

  protected function matricularCandidato(){
    if ($this->canMatricularCandidato()){
      // Dados da matrícula
      $anoLetivo = $this->getRequest()->ano_letivo;
      $cursoId = $this->getRequest()->curso_id;
      $serieId = $this->getRequest()->serie_id;
      $escolaId = $this->getRequest()->escola_id;
      $turmaId = $this->getRequest()->turma_id;

      // Dados do aluno
      $nomeAluno = Portabilis_String_utils::toLatin1($this->getRequest()->nome_aluno);
      $dataNascAluno = $this->getRequest()->data_nasc_aluno;
      $deficiencias = $this->getRequest()->deficiencias;
      $sexoAluno = $this->getRequest()->sexo_aluno;

      // Dados responsaveis
      $nomeMae = Portabilis_String_utils::toLatin1($this->getRequest()->nome_mae);
      $cpfMae = $this->getRequest()->cpf_mae;
      $nomeResponsavel = Portabilis_String_utils::toLatin1($this->getRequest()->nome_responsavel);
      $cpfResponsavel = $this->getRequest()->cpf_responsavel;

      // Dados do endereço
      $cep = $this->getRequest()->cep;
      $rua = Portabilis_String_utils::toLatin1($this->getRequest()->rua);
      $numero = $this->getRequest()->numero;
      $complemento = Portabilis_String_utils::toLatin1($this->getRequest()->complemento);
      $bairro = Portabilis_String_utils::toLatin1($this->getRequest()->bairro);
      $cidade = Portabilis_String_utils::toLatin1($this->getRequest()->cidade);
      $estado = Portabilis_String_utils::toLatin1($this->getRequest()->estado);
      $pais = Portabilis_String_utils::toLatin1($this->getRequest()->pais);

      $pessoaAlunoId = $this->createPessoa($nomeAluno);
      $pessoaMaeId = null;
      $pessoaResponsavelId = null;

      if(is_numeric($cpfMae)){
        $pessoaMaeId = $this->createOrUpdatePessoaResponsavel($cpfMae, $nomeMae);
        $this->createOrUpdatePessoaFisicaResponsavel($pessoaMaeId, $cpfMae);
      }

      if(is_numeric($cpfResponsavel)){
        $pessoaResponsavelId = $this->createOrUpdatePessoaResponsavel($cpfResponsavel, $nomeResponsavel);
        $this->createOrUpdatePessoaFisicaResponsavel($pessoaResponsavelId, $cpfResponsavel);
      }

      $this->createOrUpdatePessoaFisica($pessoaAlunoId, $pessoaResponsavelId, $pessoaMaeId, $dataNascimento, $sexoAluno);

      $alunoId = $this->createOrUpdateAluno($pessoaAlunoId);

      if(is_array($deficiencias))
        $this->updateDeficiencias($pessoaAlunoId, $deficiencias);

      if($this->_maxAlunosTurma($turmaId) <= $this->_alunosMatriculadosTurma($turmaId)){
      	// $this->messenger->append("max alunos turma: " . $this->_maxAlunosTurma($turmaId) . "alunos matriculados na turma: " . $this->_alunosMatriculadosTurma($turmaId));
      	$this->messenger->append("Aparentemente não existem vagas disponíveis para a seleção informada. Altere a seleção e tente novamente.");
      	return array("cod_matricula" => 0);
  	  }

      return array("cod_matricula" => $this->cadastraMatricula($escolaId, $serieId, $anoLetivo, $cursoId, $alunoId, $turmaId));

      // @TODO CRIAR/GRAVAR ENDEREÇO
    }
  }*/

  protected function cadastraPreMatricula($escolaId, $serieId, $anoLetivo, $cursoId, $alunoId, $turnoId){
    $obj = new clsPmieducarMatricula(NULL, NULL,
        $escolaId, $serieId, NULL,
        1, $alunoId, 11, NULL, NULL, 1, $anoLetivo,
        1, NULL, NULL, NULL, NULL, $cursoId,
        NULL, NULL, date('Y-m-d'));
    $obj->turno_pre_matricula = $turnoId;

    $matriculaId = $obj->cadastra();

    return $matriculaId;
  }

  protected function enturmaPreMatricula($alunoId, $turmaId, $matriculaId, $maeIsResponsavel){
  	// $this->messenger->append($escolaId, $serieId, $anoLetivo, $cursoId, $alunoId, $turmaId, $matriculaId);

  	$obj_a = new clsPmieducarAluno($alunoId);
	  $obj_a->ativo = 1;
	if($maeIsResponsavel){
		$obj_a->tipo_responsavel = 'm';
	}
	$obj_a->edita();

    $obj_m = new clsPmieducarMatricula($matriculaId);
    $obj_m->aprovado = 3;
    $obj_m->ativo = 1;
    $obj_m->edita();

    $enturmacao = new clsPmieducarMatriculaTurma($matriculaId,
                                                 $turmaId,
                                                1,
                                                 1,
                                                 NULL,
                                                 NULL,
                                                 1);
    $enturmacao->data_enturmacao = date('Y-m-d');
    $enturmacao->cadastra();
    return $matriculaId;
  }

  protected function updateDeficiencias($pessoaId, $deficiencias) {
    $sql = "delete from cadastro.fisica_deficiencia where ref_idpes = $1";
    $this->fetchPreparedQuery($sql, $pessoaId, false);

    foreach ($deficiencias as $id) {
      if (! empty($id)) {
        $deficiencia = new clsCadastroFisicaDeficiencia($pessoaId, $id);
        $deficiencia->cadastra();
      }
    }
  }

  protected function createPessoa($nome) {
    $pessoa        = new clsPessoa_();
    $pessoa->nome  = addslashes($nome);

    $pessoa->tipo      = 'F';

    return $pessoa->cadastra();
  }

  protected function createOrUpdatePessoaResponsavel($cpf, $nome) {
    $pessoa        = new clsPessoa_();
    $pessoa->nome  = addslashes($nome);
    $pessoa->idpes_cad = 1;
    $pessoa->idpes_rev = 1;

    $sql = "select idpes from cadastro.fisica WHERE cpf = $1 limit 1";
    $pessoaId = Portabilis_Utils_Database::selectField($sql, $cpf);

    if (! $pessoaId || !$pessoaId > 0) {
      $pessoa->tipo      = 'F';
      $pessoaId          = $pessoa->cadastra();
    }
    else {
      $pessoa->idpes = $pessoaId;
      $pessoa->data_rev  = date('Y-m-d H:i:s', time());
      $pessoa->edita();
    }

    return $pessoaId;
  }

  protected function createOrUpdatePessoaFisica($pessoaId, $pessoaResponsavelId, $pessoaMaeId, $dataNascimento, $sexo) {
    $fisica                       = new clsFisica();
    $fisica->idpes                = $pessoaId;
    $fisica->data_nasc            = $dataNascimento;
    $fisica->idpes_cad            = 1;
    $fisica->idpes_rev            = 1;
    $fisica->sexo                 = strtoupper($sexo);

    $sql = "select 1 from cadastro.fisica WHERE idpes = $1 limit 1";

    if(is_numeric($pessoaResponsavelId))
      $fisica->idpes_responsavel = $pessoaResponsavelId;
    elseif(is_numeric($pessoaMaeId)){
      $fisica->idpes_mae = $pessoaMaeId;
      $fisica->idpes_responsavel = $pessoaMaeId;
    }

    if(is_numeric($pessoaResponsavelId) && is_numeric($pessoaMaeId))
      $fisica->idpes_mae = $pessoaMaeId;

    if (Portabilis_Utils_Database::selectField($sql, $pessoaId) != 1)
      $fisica->cadastra();
    else
      $fisica->edita();
  }

  protected function createOrUpdatePessoaFisicaResponsavel($pessoaId, $cpf) {
    $fisica                       = new clsFisica();
    $fisica->idpes                = $pessoaId;
    $fisica->cpf                  = $cpf;
    $fisica->idpes_cad            = 1;
    $fisica->idpes_rev            = 1;

    $sql = "select 1 from cadastro.fisica WHERE idpes = $1 limit 1";

    if (Portabilis_Utils_Database::selectField($sql, $pessoaId) != 1)
      $fisica->cadastra();
    else
      $fisica->edita();
  }

  protected function createOrUpdateAluno($pessoaId, $ativo) {
    $aluno                       = new clsPmieducarAluno();
    $aluno->ref_idpes            = $pessoaId;

    $detalhe = $aluno->detalhe();

    if (!$detalhe)
      $retorno = $aluno->cadastra();
    else
      $retorno = $detalhe['cod_aluno'];

  	if($ativo == 0){
  		$aluno = new clsPmieducarAluno($retorno);
  		$aluno->ativo = 0;
  		$aluno->edita();
  	}

    return $retorno;
  }

  protected function _maxAlunosTurma($turmaId){
  	$obj_t = new clsPmieducarTurma($turmaId);
    $det_t = $obj_t->detalhe();
    $maxAlunosTurma = $det_t['max_aluno'];
    return $maxAlunosTurma;
  }

  protected function _alunosMatriculadosTurma($turmaId){
  	$obj_mt = new clsPmieducarMatriculaTurma($turmaId);

    return count(array_filter(($obj_mt->lista($int_ref_cod_matricula = NULL, $int_ref_cod_turma = $turmaId,
              								  $int_ref_usuario_exc = NULL, $int_ref_usuario_cad = NULL,
              								  $date_data_cadastro_ini = NULL, $date_data_cadastro_fim = NULL,
              								  $date_data_exclusao_ini = NULL, $date_data_exclusao_fim = NULL, $int_ativo = 1,
              								  $int_ref_cod_serie = $this->ref_cod_serie, $int_ref_cod_curso = NULL,
              								  $int_ref_cod_escola = NULL,
              								  $int_ref_cod_instituicao = $this->getRequest()->instituicao_id))));
  }

  protected function canCancelarPreMatricula(){
    return $this->validatesExistenceOf('matricula', $this->getRequest()->matricula_id);
  }

  protected function cancelarPreMatricula(){
    if ($this->canCancelarPreMatricula()){
      $matriculaId = $this->getRequest()->matricula_id;

      $alunoId = Portabilis_Utils_Database::selectField('SELECT ref_cod_aluno FROM pmieducar.matricula WHERE cod_matricula = $1', array($matriculaId));
      $pessoaId = Portabilis_Utils_Database::selectField('SELECT ref_idpes FROM pmieducar.aluno WHERE cod_aluno = $1', array($alunoId));
      $pessoaMaeId = Portabilis_Utils_Database::selectField('SELECT idpes_mae FROM cadastro.fisica WHERE idpes = $1', array($pessoaId));
      $pessoaRespId = Portabilis_Utils_Database::selectField('SELECT idpes_responsavel FROM cadastro.fisica WHERE idpes = $1', array($pessoaId));

      if(is_numeric($matriculaId)){
        $this->fetchPreparedQuery('DELETE FROM pmieducar.matricula_turma WHERE ref_cod_matricula = $1', array($matriculaId));
        $this->fetchPreparedQuery('DELETE FROM pmieducar.matricula WHERE cod_matricula = $1', array($matriculaId));
      }

      if(is_numeric($alunoId))
        $this->fetchPreparedQuery('DELETE FROM pmieducar.aluno WHERE cod_aluno = $1', $alunoId);

      if(is_numeric($pessoaId)){
        $this->fetchPreparedQuery('DELETE FROM cadastro.fisica WHERE idpes = $1', $pessoaId);
        $this->fetchPreparedQuery('DELETE FROM cadastro.pessoa WHERE idpes = $1', $pessoaId);
      }
      if(is_numeric($pessoaMaeId)){
        $this->fetchPreparedQuery('DELETE FROM cadastro.fisica WHERE idpes = $1', $pessoaMaeId);
        $this->fetchPreparedQuery('DELETE FROM cadastro.pessoa WHERE idpes = $1', $pessoaMaeId);
      }

      if(is_numeric($pessoaRespId)){
        $this->fetchPreparedQuery('DELETE FROM cadastro.fisica WHERE idpes = $1', $pessoaRespId);
        $this->fetchPreparedQuery('DELETE FROM cadastro.pessoa WHERE idpes = $1', $pessoaRespId);
      }
    }
  }

  protected function createOrUpdateEndereco($pessoaAlunoId, $cep, $rua, $numero, $complemento, $bairro, $cidade, $estado, $pais) {

  	$municipioId  = Portabilis_Utils_Database::selectField("SELECT idmun FROM public.municipio WHERE translate(upper(nome),'ÅÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÝÑ','AAAAAAEEEEIIIIOOOOOUUUUCYN') = translate(upper($1),'ÅÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÝÑ','AAAAAAEEEEIIIIOOOOOUUUUCYN') limit 1", $cidade);
  	$distritoId   = Portabilis_Utils_Database::selectField("SELECT iddis FROM public.distrito WHERE translate(upper(nome),'ÅÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÝÑ','AAAAAAEEEEIIIIOOOOOUUUUCYN') = translate(upper($1),'ÅÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÝÑ','AAAAAAEEEEIIIIOOOOOUUUUCYN') limit 1", $cidade);

  	if($municipioId){
  		$bairroId     = Portabilis_Utils_Database::selectField("SELECT idbai FROM public.bairro WHERE translate(upper(nome),'ÅÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÝÑ','AAAAAAEEEEIIIIOOOOOUUUUCYN') = translate(upper($1),'ÅÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÝÑ','AAAAAAEEEEIIIIOOOOOUUUUCYN') and idmun = $2 limit 1", array($bairro, $municipioId));
      $logradouroId = Portabilis_Utils_Database::selectField("SELECT idlog FROM public.logradouro WHERE translate(upper(nome),'ÅÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÝÑ','AAAAAAEEEEIIIIOOOOOUUUUCYN') = translate(upper($1),'ÅÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÝÑ','AAAAAAEEEEIIIIOOOOOUUUUCYN') and idmun = $2 limit 1", array($rua, $municipioId));
    }

  	// $this->messenger->append("Bairro: " . $bairroId . "Logradouro: " . $logradouroId . "Municipio: " . $municipioId . "Distrito: " . $distritoId . "cep: " . $cep);
  	// $this->messenger->append(" parametros: " . $pessoaAlunoId." ". $cep." ". $rua." ". $numero." ". $complemento." ". $bairro." ". $cidade." ". $estado." ". $pais);
    if ($cep && is_numeric($bairroId) && is_numeric($logradouroId)){
      $this->_createOrUpdatePessoaEndereco($pessoaAlunoId, $cep, $logradouroId, $numero, $complemento, $bairroId);
    } else if($cep && is_numeric($municipioId) && is_numeric($distritoId)){

      if (!is_numeric($bairroId)){
          $bairroId = $this->createBairro($bairro, $municipioId, $distritoId);
      }
      if (!is_numeric($logradouroId)){
          $logradouroId = $this->createLogradouro($rua, $municipioId);
      }

      $this->_createOrUpdatePessoaEndereco($pessoaAlunoId, $cep, $logradouroId, $numero, $complemento, $bairroId);

    }else{
      $endereco = new clsPessoaEndereco($pessoaAlunoId);
      $endereco->exclui();
    }
  }

  protected function _createOrUpdatePessoaEndereco($pessoaId, $cep, $logradouroId, $numero, $complemento, $bairroId) {

    $objCepLogradouro = new ClsCepLogradouro($cep, $logradouroId);

    if (! $objCepLogradouro->existe())
      $objCepLogradouro->cadastra();

    $objCepLogradouroBairro = new ClsCepLogradouroBairro();
    $objCepLogradouroBairro->cep = $cep;
    $objCepLogradouroBairro->idbai = $bairroId;
    $objCepLogradouroBairro->idlog = $logradouroId;

    if (! $objCepLogradouroBairro->existe())
      $objCepLogradouroBairro->cadastra();

    $endereco = new clsPessoaEndereco(
      $pessoaId,
      $cep,
      $logradouroId,
      $bairroId,
      $numero,
      $complemento,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      1,
      1
    );

    // $this->messenger->append(" pessoaid: " . $pessoaId . "CEP: " . $cep . "Logradouro: " . $logradouroId . "Bairro: " . $bairroId . "Número: " . $numero . "COmplemento:  " . $complemento);
    // forçado exclusão, assim ao cadastrar endereco_pessoa novamente,
    // será excluido endereco_externo (por meio da trigger fcn_aft_ins_endereco_pessoa).
    $endereco->exclui();
    $endereco->cadastra();
  }

  protected function createBairro($bairro, $municipioId, $distritoId){
    $objBairro = new clsBairro(null,$municipioId,null,addslashes($bairro), 1);
    $objBairro->iddis = $distritoId;
    return $objBairro->cadastra();
  }

  protected function createLogradouro($logradouro, $municipioId){
    $objLogradouro = new clsLogradouro(null,'RUA', $logradouro, $municipioId,
                                           null, 'S', 1);
    return $objLogradouro->cadastra();
  }

  protected function excluirInformacoesAluno($alunoId){

      $pessoaId = Portabilis_Utils_Database::selectField('SELECT ref_idpes FROM pmieducar.aluno WHERE cod_aluno = $1', array($alunoId));
      if(is_numeric($pessoaId)){
      	$pessoaMaeId = Portabilis_Utils_Database::selectField('SELECT idpes_mae FROM cadastro.fisica WHERE idpes = $1', array($pessoaId));
      	$pessoaRespId = Portabilis_Utils_Database::selectField('SELECT idpes_responsavel FROM cadastro.fisica WHERE idpes = $1', array($pessoaId));
  	  }
      if(is_numeric($alunoId))
        $this->fetchPreparedQuery('DELETE FROM pmieducar.aluno WHERE cod_aluno = $1', $alunoId);

      if(is_numeric($pessoaId)){
        $this->fetchPreparedQuery('DELETE FROM cadastro.fisica WHERE idpes = $1', $pessoaId);
        $this->fetchPreparedQuery('DELETE FROM cadastro.pessoa WHERE idpes = $1', $pessoaId);
      }
      if(is_numeric($pessoaMaeId)){
        $this->fetchPreparedQuery('DELETE FROM cadastro.fisica WHERE idpes = $1', $pessoaMaeId);
        $this->fetchPreparedQuery('DELETE FROM cadastro.pessoa WHERE idpes = $1', $pessoaMaeId);
      }

      if(is_numeric($pessoaRespId)){
        $this->fetchPreparedQuery('DELETE FROM cadastro.fisica WHERE idpes = $1', $pessoaRespId);
        $this->fetchPreparedQuery('DELETE FROM cadastro.pessoa WHERE idpes = $1', $pessoaRespId);
      }

  }
  public function Gerar() {
    // if ($this->isRequestFor('post', 'matricular-candidato'))
      // $this->appendResponse($this->matricularCandidato());
    if ($this->isRequestFor('post', 'registrar-pre-matricula'))
      $this->appendResponse($this->registrarPreMatricula());
  	elseif ($this->isRequestFor('post', 'homologar-pre-matricula'))
      $this->appendResponse($this->homologarPreMatricula());
    elseif ($this->isRequestFor('post', 'cancelar-pre-matricula'))
      $this->appendResponse($this->cancelarPreMatricula());
    else
      $this->notImplementedOperationError();
  }
}