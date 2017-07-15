namespace Cadett

module Calculator =
    (*
        Definicja opcji dla poszczególnych instrukcji kalkulatora.
    *)
    type Instruction =
        | ADD               (* dodawanie *)
        | SUB               (* odejmowanie *)
        | MUL               (* mnożenie *)
        | DIV               (* dzielenie *)
        | SQR               (* pierwiastek *)
        | PUSH of float     (* odkładanie na stos *)

    // skrócony typ listy typu float
    type stack = float list

    // skrócony typ listy typu Instruction
    type inslist = Instruction list

    // definicja wyjątku dla błędu wykonania instrukcji
    exception InstructionError

    (*
        Definicja wyjątku dla błędu który wystąpił podczas działania programu.
        Jako parametr przyjmuje listę pozostałych do wykonania instrukcji.
    *)
    exception InvalidProgram of (inslist)

    (*
        Wykonuje podaną instrukcję na elementach na stosie.
        
        PARAMETERS:
            x - Instrukcja do wykonania.
            y - Stos zawierający liczby przekazywane do instrukcji.

        EXCEPTION:
            InstructionError - Gdy w trakcie wykonywania operacji wystąpi bład.
    *)
    let intInstr (x: Instruction) (y: stack) : stack =
        try
            match x, y with
            | ADD,    a::b::ys -> (b + a) :: ys
            | SUB,    a::b::ys -> (b - a) :: ys
            | MUL,    a::b::ys -> (b * a) :: ys
            | DIV,    a::b::ys -> (b / a) :: ys
            | SQR,    a::ys    -> (a * a) :: ys
            | PUSH x, ys       -> x :: ys
            | _,      _        -> raise InstructionError
        with
        | :? System.DivideByZeroException -> raise InstructionError
        | :? System.OverflowException -> raise InstructionError

    (*
        Zamienia podaną instrukcję na tekstowy odpowiednik.

        PARAMETERS:
            x - Instrukcja do konwersji.. 
    *)
    let printSingleInstr = function
        | (ADD)    -> "ADD\n"
        | (SUB)    -> "SUB\n"
        | (MUL)    -> "MUL\n"
        | (DIV)    -> "DIV\n"
        | (SQR)    -> "SQR\n"
        | (PUSH x) -> "PUSH " + System.Convert.ToString(x) + "\n"

    (*
        Zamienia listę instrukcji na tekstowy odpowiednik.
        Funkcja wywołuje funcję :printSingleInstr.

        PARAMETERS:
            is - Lista instrukcji do konwersji.
    *)
    let rec printInstrList (is: inslist) =
        match is with
        | [] -> "---"
        | i::is -> printSingleInstr(is.Head) + printInstrList(is.Tail)

    (*
        Wykonuje program kalkulatora używając podanych instrukcji.
        
        PARAMETERS:
            is - Lista instrukcji do wykonania.
    *)
    let intProg (is: Instruction list) =
        (*
            Wykonuje wszystkie podane instrukcje używając rekurencji.

            PARAMETERS:
                is - Lista instrukcji do wykonania.
                xs - Aktualny stos zawierający podane elementy programu.

            EXCEPTION:
                InvalidProgram - Gdy w trakcie działania funkcji wystąpi błąd argumentów.
        *)
        let rec iPS = function
            | ([], x::xs) -> x
            | (i::is, xs) ->
                try
                    iPS( is, (intInstr i xs) )
                with
                    InstructionError -> raise (InvalidProgram(i::is))
            | (is,    _ ) -> raise (InvalidProgram(is))

        // wywołuje rekurencyjną funkcję główną programu
        try
            iPS(is, [])
        with
            | InvalidProgram(sl) ->
                printfn "ERROR: Invalid program on instruction '%A'.\nRemaining instructions:\n%A"
                    sl.Head (printInstrList(sl.Tail))
                0.0 

    (*
        Funkcja uruchamiana zaraz po wczytaniu skryptu.
    *)
    let mainFunc() =
        let il1 = [PUSH 3.0; PUSH 4.0; ADD; PUSH 2.0; MUL]
        let il2 = [PUSH 3.0; PUSH 4.0; ADD; PUSH 2.0; ADD; SQR]
        let il3 = [PUSH 3.0; PUSH 4.0; ADD; PUSH 0.0; MUL]

        let w1 = intProg il1
        let w2 = intProg il2
        let w3 = intProg il3

        printfn "%A :: %A :: %A" w1 w2 w3

        0

    let testA   = float 2
    let testB x = float 2
    let testC x = float 2 + x
    let testD x = x.ToString().Length
    let testE (x:float) = x.ToString().Length
    let testF x = printfn "%s" x
    let testG x = printfn "%f" x
    let testH   = 2 * 2 |> ignore
    let testI x = 2 * 2 |> ignore
    let testJ (x:int) = 2 * 2 |> ignore
    let testK   = "hello"
    let testL() = "hello"
    let testM x = x=x
    let testN x = x 1          // hint: what kind of thing is x?
    let testO x:string = x 1   // hint: what does :string modify?
